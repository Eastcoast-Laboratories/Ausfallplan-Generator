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
        // Only admin can access
        $user = $this->Authentication->getIdentity();
        if ($user->role !== 'admin') {
            $this->Flash->error(__('Access denied.'));
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
                'user_count' => $this->Organizations->find()->func()->count('DISTINCT Users.id'),
                'children_count' => $this->Organizations->find()->func()->count('DISTINCT Children.id'),
            ])
            ->leftJoinWith('Users')
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
        $user = $this->Authentication->getIdentity();
        if ($user->role !== 'admin') {
            $this->Flash->error(__('Access denied.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

        $organization = $this->Organizations->get($id, [
            'contain' => ['Users', 'Children']
        ]);

        // Get schedules count for this org
        $schedulesCount = $this->fetchTable('Schedules')->find()
            ->innerJoinWith('Users')
            ->where(['Users.organization_id' => $id])
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
        $user = $this->Authentication->getIdentity();
        if ($user->role !== 'admin') {
            $this->Flash->error(__('Access denied.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

        $organization = $this->Organizations->get($id, [
            'contain' => ['Users']
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
        $user = $this->Authentication->getIdentity();
        if ($user->role !== 'admin') {
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
                ->innerJoinWith('Users')
                ->where(['Users.organization_id' => $id])
                ->all();
            foreach ($schedules as $schedule) {
                $schedulesTable->delete($schedule);
            }

            // 2. Delete children (depends on organization)
            $childrenTable = $this->fetchTable('Children');
            $children = $childrenTable->find()
                ->where(['organization_id' => $id])
                ->all();
            foreach ($children as $child) {
                $childrenTable->delete($child);
            }

            // 3. Delete sibling groups (depends on organization)
            $siblingGroupsTable = $this->fetchTable('SiblingGroups');
            $siblingGroups = $siblingGroupsTable->find()
                ->where(['organization_id' => $id])
                ->all();
            foreach ($siblingGroups as $group) {
                $siblingGroupsTable->delete($group);
            }

            // 4. Delete users (depends on organization)
            $usersTable = $this->fetchTable('Users');
            $users = $usersTable->find()
                ->where(['organization_id' => $id])
                ->all();
            foreach ($users as $userToDelete) {
                $usersTable->delete($userToDelete);
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
        $user = $this->Authentication->getIdentity();
        if ($user->role !== 'admin') {
            $this->Flash->error(__('Access denied.'));
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
        $user = $this->Authentication->getIdentity();
        if ($user->role !== 'admin') {
            $this->Flash->error(__('Access denied.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

        $this->request->allowMethod(['post']);
        $userId = $this->request->getData('user_id');

        if ($userId) {
            $usersTable = $this->fetchTable('Users');
            $userToUpdate = $usersTable->get($userId);
            $userToUpdate->organization_id = $id;

            if ($usersTable->save($userToUpdate)) {
                $this->Flash->success(__('User has been added to organization.'));
            } else {
                $this->Flash->error(__('Could not add user to organization.'));
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
        $user = $this->Authentication->getIdentity();
        if ($user->role !== 'admin') {
            $this->Flash->error(__('Access denied.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

        $this->request->allowMethod(['post']);

        // Find "keine organisation"
        $noOrg = $this->Organizations->find()
            ->where(['name' => 'keine organisation'])
            ->first();

        if (!$noOrg) {
            // Create if it doesn't exist
            $noOrg = $this->Organizations->newEntity(['name' => 'keine organisation']);
            $this->Organizations->save($noOrg);
        }

        $usersTable = $this->fetchTable('Users');
        $userToUpdate = $usersTable->get($userId);
        $userToUpdate->organization_id = $noOrg->id;

        if ($usersTable->save($userToUpdate)) {
            $this->Flash->success(__('User has been removed from organization.'));
        } else {
            $this->Flash->error(__('Could not remove user from organization.'));
        }

        return $this->redirect(['action' => 'edit', $id]);
    }
}
