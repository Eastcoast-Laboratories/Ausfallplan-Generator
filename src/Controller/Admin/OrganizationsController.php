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
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        // Only system admin can access
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Access denied. Please login.'));
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData(); // Get User entity from Identity
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Access denied. System admin privileges required.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

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
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Access denied. System admin privileges required.'));
            return $this->redirect(['_name' => 'dashboard']);
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
     * Edit method - Edit organization
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
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Access denied. System admin privileges required.'));
            return $this->redirect(['_name' => 'dashboard']);
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
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['_name' => 'dashboard']);
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
            
            // 1. Delete schedules (depends on users)
            $schedulesTable = $this->fetchTable('Schedules');
            $schedules = $schedulesTable->find()
                ->where(['Schedules.organization_id' => $id])
                ->all();
            foreach ($schedules as $schedule) {
                $schedulesTable->delete($schedule);
            }

            // 2. Delete children (linked via organization_users)
            // Children are linked via their user's organization, so they get deleted via cascade when users are deleted
            // No direct action needed here as Children don't have organization_id

            // 3. Delete sibling groups (linked via organization)
            // SiblingGroups are linked via their organization, cascade will handle deletion
            // Or handle via organization relationship

            // 4. Delete organization_users entries (cascade will handle user cleanup if needed)
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $orgUsers = $orgUsersTable->find()
                ->where(['organization_id' => $id])
                ->all();
            foreach ($orgUsers as $orgUser) {
                $orgUsersTable->delete($orgUser);
            }

            // 5. Finally delete the organization
            if ($this->Organizations->delete($organization)) {
                $connection->commit();
                $this->Flash->success(__('Die Organisation und alle zugehörigen Daten wurden gelöscht.'));
            } else {
                $connection->rollback();
                $this->Flash->error(__('Die Organisation konnte nicht gelöscht werden. Bitte versuchen Sie es erneut.'));
            }

        } catch (\Exception $e) {
            $connection->rollback();
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
