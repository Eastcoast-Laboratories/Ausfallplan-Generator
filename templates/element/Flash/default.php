<?php
/**
 * Custom Flash Message Template
 * Provides a modern, centered, and attention-grabbing design for all flash messages
 */

$class = 'flash-message';
$class .= ' flash-' . h($params['class'] ?? 'info');
?>

<div class="<?= $class ?>" onclick="this.classList.add('flash-dismissed')">
    <div class="flash-content">
        <div class="flash-icon">
            <?php if (isset($params['class'])): ?>
                <?php if ($params['class'] === 'error'): ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                <?php elseif ($params['class'] === 'success'): ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                <?php elseif ($params['class'] === 'warning' || $params['class'] === 'info'): ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="flash-message-text">
            <?= h($message) ?>
        </div>
        <button class="flash-close" onclick="this.parentElement.parentElement.remove(); event.stopPropagation();">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
</div>

<style>
.flash-message {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    min-width: 400px;
    max-width: 600px;
    margin: 0 auto;
    padding: 0;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease-out;
    cursor: pointer;
}

@keyframes slideDown {
    from {
        transform: translateX(-50%) translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
}

.flash-dismissed {
    animation: slideUp 0.3s ease-out forwards;
}

@keyframes slideUp {
    to {
        transform: translateX(-50%) translateY(-100%);
        opacity: 0;
    }
}

.flash-content {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 24px;
    position: relative;
}

.flash-icon {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
}

.flash-message-text {
    flex-grow: 1;
    font-size: 16px;
    font-weight: 500;
    line-height: 1.5;
}

.flash-close {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.flash-close:hover {
    opacity: 1;
    background: rgba(0, 0, 0, 0.1);
}

/* Error style - RED with high contrast */
.flash-error {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    border: 2px solid #f87171;
}

.flash-error .flash-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Success style - GREEN */
.flash-success {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: white;
    border: 2px solid #4ade80;
}

.flash-success .flash-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Warning style - ORANGE */
.flash-warning {
    background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
    color: white;
    border: 2px solid #fb923c;
}

.flash-warning .flash-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Info style - BLUE */
.flash-info {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
    border: 2px solid #60a5fa;
}

.flash-info .flash-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Mobile responsive */
@media (max-width: 640px) {
    .flash-message {
        min-width: 90%;
        max-width: 90%;
        top: 10px;
    }
    
    .flash-content {
        padding: 14px 16px;
        gap: 10px;
    }
    
    .flash-message-text {
        font-size: 14px;
    }
}

/* Auto-hide after 5 seconds */
.flash-message {
    animation: slideDown 0.3s ease-out, fadeOut 0.5s ease-out 4.5s forwards;
}

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateX(-50%) translateY(-100%);
    }
}
</style>

<script>
// Auto-dismiss after 5 seconds
setTimeout(() => {
    const flash = document.querySelector('.flash-message');
    if (flash && !flash.classList.contains('flash-dismissed')) {
        flash.classList.add('flash-dismissed');
        setTimeout(() => flash.remove(), 300);
    }
}, 5000);
</script>
