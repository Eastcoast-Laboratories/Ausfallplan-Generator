<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

/**
 * API Organizations Controller
 * Provides JSON API for organization autocomplete
 */
class OrganizationsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // RequestHandler is loaded automatically in AppController
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        // Allow unauthenticated access for registration
        $this->Authentication->addUnauthenticatedActions(['search']);
        // toggleEncryption requires authentication and admin role (checked in method)
    }

    /**
     * Search organizations for autocomplete
     *
     * @return void
     */
    public function search()
    {
        $this->request->allowMethod(['get']);
        $query = $this->request->getQuery('q', '');
        
        $organizations = [];
        
        // Minimum 2 characters required
        if (strlen($query) >= 2) {
            $organizationsTable = $this->fetchTable('Organizations');
            $results = $organizationsTable->find()
                ->where([
                    'name !=' => 'keine organisation',
                    'name LIKE' => '%' . $query . '%'
                ])
                ->select(['id', 'name'])
                ->orderBy(['name' => 'ASC'])
                ->limit(50)
                ->all();
            
            foreach ($results as $org) {
                $organizations[] = [
                    'id' => $org->id,
                    'name' => $org->name
                ];
            }
        }
        
        // Return JSON directly
        $this->response = $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['organizations' => $organizations]));
        
        return $this->response;
    }

    /**
     * Toggle encryption for an organization
     * Admin-only endpoint
     *
     * @param int|null $id Organization ID
     * @return void
     */
    public function toggleEncryption($id = null)
    {
        $this->request->allowMethod(['post', 'put']);
        
        // Get authenticated user
        $user = $this->Authentication->getIdentity();
        if (!$user) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Authentication required')]));
            return $this->response;
        }
        
        // Check if user is org admin
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgUser = $orgUsersTable->find()
            ->where([
                'organization_id' => $id,
                'user_id' => $user->id,
                'role' => 'org_admin'
            ])
            ->first();
        
        if (!$orgUser && !$user->is_system_admin) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Admin access required')]));
            return $this->response;
        }
        
        // Get organization
        $organizationsTable = $this->fetchTable('Organizations');
        try {
            $organization = $organizationsTable->get($id);
        } catch (\Exception $e) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Organization not found')]));
            return $this->response;
        }
        
        // Toggle encryption_enabled
        $organization->encryption_enabled = !$organization->encryption_enabled;
        
        if ($organizationsTable->save($organization)) {
            $status = $organization->encryption_enabled ? __('enabled') : __('disabled');
            $this->response = $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'encryption_enabled' => $organization->encryption_enabled,
                    'message' => __('Encryption has been {0}', $status)
                ]));
        } else {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Failed to update encryption setting')]));
        }
        
        return $this->response;
    }

    /**
     * Wrap DEK for a new user
     * Admin wraps organization DEK with new user's public key
     *
     * @param int|null $id Organization ID
     * @return void
     */
    public function wrapDek($id = null)
    {
        $this->request->allowMethod(['post']);
        
        // Get authenticated user (must be admin)
        $user = $this->Authentication->getIdentity();
        if (!$user) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Authentication required')]));
            return $this->response;
        }
        
        // Check if user is org admin
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgUser = $orgUsersTable->find()
            ->where([
                'organization_id' => $id,
                'user_id' => $user->id,
                'role' => 'org_admin'
            ])
            ->first();
        
        if (!$orgUser && !$user->is_system_admin) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Admin access required')]));
            return $this->response;
        }
        
        // Get request data
        $data = $this->request->getData();
        $newUserId = $data['user_id'] ?? null;
        $publicKey = $data['public_key'] ?? null;
        $wrappedDek = $data['wrapped_dek'] ?? null;
        
        if (!$newUserId || !$wrappedDek) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Missing required parameters')]));
            return $this->response;
        }
        
        // Create or update wrapped DEK entry
        $encryptedDeksTable = $this->fetchTable('EncryptedDeks');
        $existing = $encryptedDeksTable->find()
            ->where([
                'organization_id' => $id,
                'user_id' => $newUserId
            ])
            ->first();
        
        if ($existing) {
            // Update existing
            $existing->wrapped_dek = $wrappedDek;
            $entity = $existing;
        } else {
            // Create new
            $entity = $encryptedDeksTable->newEntity([
                'organization_id' => $id,
                'user_id' => $newUserId,
                'wrapped_dek' => $wrappedDek,
            ]);
        }
        
        if ($encryptedDeksTable->save($entity)) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => __('DEK wrapped successfully')
                ]));
        } else {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Failed to save wrapped DEK')]));
        }
        
        return $this->response;
    }

    /**
     * Revoke DEK for a user (remove their wrapped DEK)
     * Admin-only endpoint
     *
     * @param int|null $id Organization ID
     * @param int|null $userId User ID
     * @return void
     */
    public function revokeDek($id = null, $userId = null)
    {
        $this->request->allowMethod(['delete', 'post']);
        
        // Get authenticated user (must be admin)
        $user = $this->Authentication->getIdentity();
        if (!$user) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Authentication required')]));
            return $this->response;
        }
        
        // Check if user is org admin
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgUser = $orgUsersTable->find()
            ->where([
                'organization_id' => $id,
                'user_id' => $user->id,
                'role' => 'org_admin'
            ])
            ->first();
        
        if (!$orgUser && !$user->is_system_admin) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Admin access required')]));
            return $this->response;
        }
        
        // Find and delete wrapped DEK
        $encryptedDeksTable = $this->fetchTable('EncryptedDeks');
        $dek = $encryptedDeksTable->find()
            ->where([
                'organization_id' => $id,
                'user_id' => $userId
            ])
            ->first();
        
        if (!$dek) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Wrapped DEK not found')]));
            return $this->response;
        }
        
        if ($encryptedDeksTable->delete($dek)) {
            $this->response = $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => __('DEK revoked successfully')
                ]));
        } else {
            $this->response = $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode(['success' => false, 'message' => __('Failed to revoke DEK')]));
        }
        
        return $this->response;
    }
}
