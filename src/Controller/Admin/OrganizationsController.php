<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;

/**
 * Admin Organizations Controller
 *
 * @property \App\Model\Table\OrganizationsTable $Organizations
 */
class OrganizationsController extends AppController
{
    /**
     * Index method - List all organizations with stats
     * System admins see all, editors see their own organizations
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Access denied. Please login.'));
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData(); // Get User entity from Identity
        
        // System admin sees all organizations
        if ($user->isSystemAdmin()) {
            $organizations = $this->Organizations->find()
                ->select([
                    'Organizations.id',
                    'Organizations.name',
                    'Organizations.is_active',
                    'Organizations.contact_email',
                    'Organizations.contact_phone',
                    'Organizations.created',
                    'user_count' => $this->Organizations->find()->func()->count('DISTINCT organization_users.user_id'),
                    'children_count' => $this->Organizations->find()->func()->count('DISTINCT Children.id'),
                ])
                ->leftJoin('organization_users', ['organization_users.organization_id = Organizations.id'])
                ->leftJoinWith('Children')
                ->group(['Organizations.id'])
                ->orderBy(['Organizations.name' => 'ASC'])
                ->all();
        } else {
            // Regular users see their organizations (org_admin, editor, viewer)
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $userOrgEntities = $orgUsersTable->find()
                ->where(['user_id' => $user->id])
                ->all();
            
            $userOrgsWithRoles = [];
            foreach ($userOrgEntities as $orgUser) {
                $userOrgsWithRoles[$orgUser->organization_id] = $orgUser->role;
            }
            
            if (empty($userOrgsWithRoles)) {
                // User has no organizations - show empty list with option to create one
                $organizations = [];
            } else {
                $organizations = $this->Organizations->find()
                    ->where(['Organizations.id IN' => array_keys($userOrgsWithRoles)])
                    ->select([
                        'Organizations.id',
                        'Organizations.name',
                        'Organizations.is_active',
                        'Organizations.contact_email',
                        'Organizations.contact_phone',
                        'Organizations.created',
                        'user_count' => $this->Organizations->find()->func()->count('DISTINCT organization_users.user_id'),
                        'children_count' => $this->Organizations->find()->func()->count('DISTINCT Children.id'),
                    ])
                    ->leftJoin('organization_users', ['organization_users.organization_id = Organizations.id'])
                    ->leftJoinWith('Children')
                    ->group(['Organizations.id'])
                    ->orderBy(['Organizations.name' => 'ASC'])
                    ->all();
                    
                // Add role information to each organization
                foreach ($organizations as $org) {
                    $org->user_role = $userOrgsWithRoles[$org->id] ?? 'viewer';
                }
            }
        }

        $this->set(compact('organizations'));
    }

    /**
     * View method - Display organization details
     *
     * @param string|null $id Organization id
     * @return \Cake\Http\Response|null|void
     */
    public function view($id = null)
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData();
        
        // Check if user is member of this organization (any role) OR system admin
        $canView = false;
        if ($user->isSystemAdmin()) {
            $canView = true;
        } else {
            // Check if user is member of this organization
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $membership = $orgUsersTable->find()
                ->where([
                    'user_id' => $user->id,
                    'organization_id' => $id
                ])
                ->first();
            
            if ($membership) {
                $canView = true;
            }
        }
        
        if (!$canView) {
            $this->Flash->error(__('Access denied. You are not a member of this organization.'));
            return $this->redirect(['action' => 'index']);
        }

        $organization = $this->Organizations->get($id, [
            'contain' => ['OrganizationUsers' => ['Users'], 'Children']
        ]);

        // Get schedules count for this org
        $schedulesCount = $this->fetchTable('Schedules')->find()
            ->where(['Schedules.organization_id' => $id])
            ->count();

        $this->set(compact('organization', 'schedulesCount'));
    }

    /**
     * Add method - Create new organization OR join existing one
     * System admins and editors can create organizations or request to join existing ones
     *
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData();
        // Allow system admins and editors (they will become org_admin of the new org)
        // Viewers are blocked by AuthorizationMiddleware

        $organization = $this->Organizations->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $choice = $data['organization_choice'] ?? 'new';
            
            if ($choice === 'new') {
                // Create new organization
                $organization = $this->Organizations->patchEntity($organization, [
                    'name' => $data['organization_name'],
                    'is_active' => true
                ]);
                
                if ($this->Organizations->save($organization)) {
                    // Make user org_admin of the new organization
                    $orgUsersTable = $this->fetchTable('OrganizationUsers');
                    $orgUser = $orgUsersTable->newEntity([
                        'organization_id' => $organization->id,
                        'user_id' => $user->id,
                        'role' => 'org_admin',
                        'is_primary' => true,
                        'joined_at' => new \DateTime()
                    ]);
                    
                    if ($orgUsersTable->save($orgUser)) {
                        \Cake\Log\Log::debug('User ' . $user->id . ' added to org ' . $organization->id . ' as org_admin');
                    } else {
                        \Cake\Log\Log::error('Failed to add user ' . $user->id . ' to org ' . $organization->id);
                        \Cake\Log\Log::error('Errors: ' . json_encode($orgUser->getErrors()));
                    }
                    
                    $this->Flash->success(__('Die Organisation wurde erfolgreich erstellt.'));
                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The organization could not be saved. Please, try again.'));
            } else {
                // Join existing organization
                $orgId = (int)$choice;
                $requestedRole = $data['requested_role'] ?? 'editor';
                
                $orgUsersTable = $this->fetchTable('OrganizationUsers');
                $orgUser = $orgUsersTable->newEntity([
                    'organization_id' => $orgId,
                    'user_id' => $user->id,
                    'role' => $requestedRole,
                    'is_primary' => false,
                    'joined_at' => new \DateTime()
                ]);
                
                if ($orgUsersTable->save($orgUser)) {
                    $this->Flash->success(__('Ihre Anfrage wurde gesendet. Ein Administrator wird sie prüfen.'));
                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('Die Anfrage konnte nicht gesendet werden. Bitte versuchen Sie es erneut.'));
            }
        }

        // Get list of all organizations for selection
        $organizationsList = $this->Organizations->find('list', [
            'keyField' => 'id',
            'valueField' => 'name'
        ])->orderBy(['name' => 'ASC'])->toArray();

        $this->set(compact('organization', 'organizationsList', 'user'));
    }

    /**
     * Edit method - Edit organization
     * System admins can edit all, editors can edit their own organizations
     *
     * @param string|null $id Organization id
     * @return \Cake\Http\Response|null|void
     */
    public function edit($id = null)
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData();
        
        // Only system admins and org_admins can edit (not editors)
        if (!$user->isSystemAdmin()) {
            // Check if user is org_admin of this organization
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $hasPermission = $orgUsersTable->find()
                ->where([
                    'user_id' => $user->id,
                    'organization_id' => $id,
                    'role' => 'org_admin'  // Only org_admin, not editor
                ])
                ->count() > 0;
            
            if (!$hasPermission) {
                $this->Flash->error(__('You do not have permission to edit this organization.'));
                return $this->redirect(['action' => 'index']);
            }
        }

        $organization = $this->Organizations->get($id, [
            'contain' => ['OrganizationUsers' => ['Users']]
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $organization = $this->Organizations->patchEntity($organization, $this->request->getData());
            
            if ($this->Organizations->save($organization)) {
                $this->Flash->success(__('The organization has been saved.'));
                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__('The organization could not be saved. Please, try again.'));
        }

        // Get all users for adding
        $allUsers = $this->fetchTable('Users')->find('list', [
            'keyField' => 'id',
            'valueField' => function($user) {
                return $user->email . ' (' . $user->role . ')';
            }
        ])->orderBy(['email' => 'ASC'])->toArray();

        $this->set(compact('organization', 'allUsers'));
    }

    /**
     * Delete method - Delete organization with all associated data
     * System admins can delete all, org_admins can delete their own organizations
     *
     * @param string|null $id Organization id
     * @return \Cake\Http\Response|null
     */
    public function delete($id = null)
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData();
        
        // Check if user has permission to delete this organization
        if (!$user->isSystemAdmin()) {
            // Check if user is org_admin of this organization
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $hasPermission = $orgUsersTable->find()
                ->where([
                    'user_id' => $user->id,
                    'organization_id' => $id,
                    'role' => 'org_admin'
                ])
                ->count() > 0;
            
            if (!$hasPermission) {
                $this->Flash->error(__('You do not have permission to delete this organization.'));
                return $this->redirect(['action' => 'index']);
            }
        }

        $this->request->allowMethod(['post', 'delete']);
        $organization = $this->Organizations->get($id);

        // Prevent deletion of "keine organisation"
        if ($organization->name === 'keine organisation') {
            $this->Flash->error(__('Die Standard-Organisation "keine organisation" kann nicht gelöscht werden.'));
            return $this->redirect(['action' => 'index']);
        }

        // Start transaction
        $connection = $this->Organizations->getConnection();
        $connection->begin();

        try {
            // Delete in correct order due to foreign key constraints
            // Use deleteAll() to avoid nested transaction issues
            
            // Get "keine organisation" as fallback
            $noOrg = $this->Organizations->find()->where(['name' => 'keine organisation'])->first();
            
            // 1. Reassign or delete children
            $childrenTable = $this->fetchTable('Children');
            if ($noOrg && $noOrg->id != $id) {
                // Reassign children to "keine organisation"
                $childrenTable->updateAll(
                    ['organization_id' => $noOrg->id],
                    ['organization_id' => $id]
                );
            } else {
                // Delete children if no fallback
                $childrenTable->deleteAll(['organization_id' => $id]);
            }
            
            // 2. Clear schedule_id and waitlist_order for children in this org's schedules
            $schedulesInOrg = $this->fetchTable('Schedules')
                ->find()
                ->where(['organization_id' => $id])
                ->all()
                ->extract('id')
                ->toArray();
            
            if (!empty($schedulesInOrg)) {
                // Clear waitlist assignments (set schedule_id and waitlist_order to null)
                $childrenTable->updateAll(
                    [
                        'schedule_id' => null,
                        'waitlist_order' => null
                    ],
                    ['schedule_id IN' => $schedulesInOrg]
                );
            }

            // 3. Delete schedules
            $this->fetchTable('Schedules')->deleteAll(['organization_id' => $id]);

            // 4. Delete sibling groups
            $this->fetchTable('SiblingGroups')->deleteAll(['organization_id' => $id]);

            // 5. Delete organization_users entries
            $this->fetchTable('OrganizationUsers')->deleteAll(['organization_id' => $id]);

            // 6. Finally delete the organization
            if (!$this->Organizations->delete($organization)) {
                throw new \RuntimeException('Organization could not be deleted');
            }
            
            $connection->commit();
            $this->Flash->success(__('Die Organisation und alle zugehörigen Daten wurden gelöscht.'));

        } catch (\Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollback();
            }
            $this->Flash->error(__('Fehler beim Löschen: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Toggle active status
     *
     * @param string|null $id Organization id
     * @return \Cake\Http\Response|null
     */
    public function toggleActive($id = null)
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData();
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Access denied. System admin privileges required.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

        $this->request->allowMethod(['post']);
        $organization = $this->Organizations->get($id);
        
        $organization->is_active = !$organization->is_active;
        
        if ($this->Organizations->save($organization)) {
            $status = $organization->is_active ? __('activated') : __('deactivated');
            $this->Flash->success(__('Organization has been {0}.', $status));
        } else {
            $this->Flash->error(__('Could not change organization status.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Add user to organization
     *
     * @param string|null $id Organization id
     * @return \Cake\Http\Response|null
     */
    public function addUser($id = null)
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData();
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Access denied. System admin privileges required.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

        $this->request->allowMethod(['post']);
        $userId = $this->request->getData('user_id');
        $role = $this->request->getData('role') ?? 'viewer';

        if ($userId) {
            // Check if user is already member
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $existing = $orgUsersTable->find()
                ->where([
                    'organization_id' => $id,
                    'user_id' => $userId
                ])
                ->first();
                
            if ($existing) {
                $this->Flash->error(__('User is already a member of this organization.'));
            } else {
                // Create organization_users entry
                $orgUser = $orgUsersTable->newEntity([
                    'organization_id' => $id,
                    'user_id' => $userId,
                    'role' => $role,
                    'is_primary' => false,
                    'joined_at' => new \DateTime(),
                    'invited_by' => $user->id,
                ]);
                
                if ($orgUsersTable->save($orgUser)) {
                    $this->Flash->success(__('User has been added to organization.'));
                } else {
                    $this->Flash->error(__('Could not add user to organization.'));
                }
            }
        }

        return $this->redirect(['action' => 'edit', $id]);
    }

    /**
     * Remove user from organization
     *
     * @param string|null $id Organization id
     * @param string|null $userId User id
     * @return \Cake\Http\Response|null
     */
    public function removeUser($id = null, $userId = null)
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData();
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Access denied. System admin privileges required.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

        $this->request->allowMethod(['post']);

        // Remove organization_users entry
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgUser = $orgUsersTable->find()
            ->where([
                'organization_id' => $id,
                'user_id' => $userId
            ])
            ->first();

        if ($orgUser) {
            // Check if this is the last org_admin
            $adminCount = $orgUsersTable->find()
                ->where([
                    'organization_id' => $id,
                    'role' => 'org_admin'
                ])
                ->count();
                
            if ($adminCount === 1 && $orgUser->role === 'org_admin') {
                $this->Flash->error(__('Cannot remove last admin from organization.'));
            } else if ($orgUsersTable->delete($orgUser)) {
                $this->Flash->success(__('User has been removed from organization.'));
            } else {
                $this->Flash->error(__('Could not remove user from organization.'));
            }
        } else {
            $this->Flash->error(__('User is not a member of this organization.'));
        }

        return $this->redirect(['action' => 'edit', $id]);
    }
}
