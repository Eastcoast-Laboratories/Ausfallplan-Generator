/**
 * Child Name Decryption - Central Utility
 * 
 * Automatically decrypts encrypted child names on any page.
 * Works with any element that has data-encrypted, data-iv, data-tag attributes.
 * 
 * Usage:
 * Add to any element containing an encrypted child name:
 * <span class="child-name" 
 *       data-encrypted="base64..." 
 *       data-iv="base64..." 
 *       data-tag="base64..."
 *       data-org-id="123">
 *     Placeholder Name
 * </span>
 * 
 * The script will automatically decrypt and replace the content.
 */

(function() {
    'use strict';

    /**
     * Decrypt a single child name element
     * @param {HTMLElement} element - Element with encrypted data
     * @param {number} orgId - Organization ID
     * @returns {Promise<boolean>} - Success status
     */
    async function decryptChildName(element, orgId) {
        const encrypted = element.dataset.encrypted;
        const iv = element.dataset.iv;
        const tag = element.dataset.tag;
        
        // Skip if no encrypted data
        if (!encrypted || !iv || !tag) {
            return false;
        }
        
        // Check if already decrypted
        if (element.dataset.decrypted === 'true') {
            return true;
        }
        
        try {
            // Get DEK from session storage
            const dek = await window.OrgEncryption.getDEK(orgId);
            
            if (!dek) {
                console.log(`[ChildDecryption] No DEK available for organization ${orgId}`);
                return false;
            }
            
            // Convert base64 to ArrayBuffer/Uint8Array
            const ciphertextBuffer = window.OrgEncryption.base64ToArrayBuffer(encrypted);
            const ivArray = new Uint8Array(window.OrgEncryption.base64ToArrayBuffer(iv));
            const tagArray = new Uint8Array(window.OrgEncryption.base64ToArrayBuffer(tag));
            
            // Decrypt name
            const decrypted = await window.OrgEncryption.decryptField(
                ciphertextBuffer,
                ivArray,
                tagArray,
                dek
            );
            
            // Update display
            element.textContent = decrypted;
            element.dataset.decrypted = 'true';
            element.dataset.decryptedValue = decrypted;
            
            // Dispatch event for other scripts to react
            element.dispatchEvent(new CustomEvent('child-name-decrypted', {
                detail: { decryptedName: decrypted, orgId: orgId }
            }));
            
            console.log(`[ChildDecryption] ✅ Decrypted name for org ${orgId}`);
            return true;
            
        } catch (error) {
            console.error('[ChildDecryption] Error decrypting child name:', error);
            element.dataset.decryptionError = 'true';
            return false;
        }
    }
    
    /**
     * Decrypt all child names on the page
     * Searches for elements with class 'child-name' or data-encrypted attribute
     */
    async function decryptAllChildNames() {
        if (!window.OrgEncryption) {
            console.warn('[ChildDecryption] OrgEncryption not loaded yet');
            return;
        }
        
        // Find all elements with encrypted child names
        // Supports multiple selectors for flexibility
        const selectors = [
            '.child-name[data-encrypted]',
            '[data-encrypted][data-iv][data-tag]'
        ];
        
        const elements = document.querySelectorAll(selectors.join(','));
        
        if (elements.length === 0) {
            console.log('[ChildDecryption] No encrypted child names found on this page');
            return;
        }
        
        console.log(`[ChildDecryption] Found ${elements.length} encrypted child names`);
        
        let decryptedCount = 0;
        
        for (const element of elements) {
            // Get organization ID from element or parent
            let orgId = element.dataset.orgId;
            
            if (!orgId) {
                // Try to find org ID from parent elements
                const parent = element.closest('[data-org-id]');
                if (parent) {
                    orgId = parent.dataset.orgId;
                }
            }
            
            if (!orgId) {
                console.warn('[ChildDecryption] No organization ID found for element:', element);
                continue;
            }
            
            const success = await decryptChildName(element, orgId);
            if (success) {
                decryptedCount++;
            }
        }
        
        console.log(`[ChildDecryption] Successfully decrypted ${decryptedCount}/${elements.length} names`);
    }
    
    /**
     * Initialize decryption
     * Called when DOM is ready and OrgEncryption is available
     */
    function init() {
        // Wait for OrgEncryption to be available
        if (!window.OrgEncryption) {
            console.log('[ChildDecryption] Waiting for OrgEncryption to load...');
            setTimeout(init, 100);
            return;
        }
        
        // Run decryption
        decryptAllChildNames();
        
        // Set up MutationObserver to decrypt dynamically added elements
        const observer = new MutationObserver((mutations) => {
            let hasNewEncryptedElements = false;
            
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        if (node.matches && (node.matches('.child-name[data-encrypted]') || 
                            node.querySelector('.child-name[data-encrypted]'))) {
                            hasNewEncryptedElements = true;
                            break;
                        }
                    }
                }
                if (hasNewEncryptedElements) break;
            }
            
            if (hasNewEncryptedElements) {
                console.log('[ChildDecryption] New encrypted elements detected, decrypting...');
                decryptAllChildNames();
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Expose public API
    window.ChildDecryption = {
        decryptAll: decryptAllChildNames,
        decryptElement: decryptChildName
    };
})();
