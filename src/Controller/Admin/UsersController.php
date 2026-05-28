<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;

/**
 * Admin Users Controller
 * Manages user approval and administration
 */
class UsersController extends AppController
{
    /**
     * Admin can manage all users
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Check if user is system admin
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Access denied. Please login.'));
            $event->setResult($this->redirect(['_name' => 'login']));
            $event->stopPropagation();
            return;
        }
        
        $user = $identity->getOriginalData();
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Access denied. Admin only.'));
            $event->setResult($this->redirect(['controller' => 'Dashboard', 'action' => 'index']));
            $event->stopPropagation();
        }
    }

    /**
     * List all users
     */
    public function index()
    {
        $users = $this->Users->find()
            ->contain([
                'Organizations',
                'OrganizationUsers' => [
                    'Organizations'
                ]
            ])
            ->orderBy(['Users.created' => 'DESC'])
            ->all();

        // Get children count for each user
        $childrenTable = $this->fetchTable('Children');
        $userChildCounts = [];
        foreach ($users as $user) {
            $orgIds = [];
            foreach ($user->organization_users as $orgUser) {
                if ($orgUser->organization_id) {
                    $orgIds[] = $orgUser->organization_id;
                }
            }
            if (!empty($orgIds)) {
                $count = $childrenTable->find()
                    ->where(['organization_id IN' => $orgIds])
                    ->count();
                $userChildCounts[$user->id] = $count;
            } else {
                $userChildCounts[$user->id] = 0;
            }
        }

        $this->set(compact('users', 'userChildCounts'));
    }

    /**
     * Approve pending user
     */
    public function approve($id = null)
    {
        $this->request->allowMethod(['post']);
        
        $user = $this->Users->get($id);
        $user->status = 'active';
        $user->approved_at = new \DateTime();
        $user->approved_by = $this->Authentication->getIdentity()->id;
        
        if ($this->Users->save($user)) {
            $this->Flash->success(__('User approved successfully.'));
        } else {
            $this->Flash->error(__('Could not approve user.'));
        }
        
        return $this->redirect(['action' => 'index']);
    }

    /**
     * Reject/deactivate user
     */
    public function deactivate($id = null)
    {
        $this->request->allowMethod(['post']);
        
        $user = $this->Users->get($id);
        $user->status = 'inactive';
        
        if ($this->Users->save($user)) {
            $this->Flash->success(__('User deactivated.'));
        } else {
            $this->Flash->error(__('Could not deactivate user.'));
        }
        
        return $this->redirect(['action' => 'index']);
    }

    /**
     * Delete user and their primary organization
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $user = $this->Users->get($id, [
            'contain' => ['OrganizationUsers.Organizations']
        ]);

        // Find primary organization to delete with user
        $primaryOrg = null;
        foreach ($user->organization_users as $orgUser) {
            if ($orgUser->is_primary && $orgUser->organization) {
                $primaryOrg = $orgUser->organization;
                break;
            }
        }

        $connection = $this->Users->getConnection();
        $connection->begin();

        try {
            // If user has a primary organization, delete it using same logic as OrganizationsController
            if ($primaryOrg) {
                // Prevent deletion of "keine organisation"
                if ($primaryOrg->name === 'keine organisation') {
                    $this->Flash->error(__('Cannot delete user with standard organization "keine organisation".'));
                    return $this->redirect(['action' => 'index']);
                }

                // Get "keine organisation" as fallback for reassignment
                $noOrg = $this->fetchTable('Organizations')->find()->where(['name' => 'keine organisation'])->first();
                $orgId = $primaryOrg->id;

                // 1. Reassign or delete children
                $childrenTable = $this->fetchTable('Children');
                if ($noOrg && $noOrg->id != $orgId) {
                    $childrenTable->updateAll(
                        ['organization_id' => $noOrg->id],
                        ['organization_id' => $orgId]
                    );
                } else {
                    $childrenTable->deleteAll(['organization_id' => $orgId]);
                }

                // 2. Clear waitlist assignments
                $schedulesInOrg = $this->fetchTable('Schedules')
                    ->find()
                    ->where(['organization_id' => $orgId])
                    ->all()
                    ->extract('id')
                    ->toArray();

                if (!empty($schedulesInOrg)) {
                    $childrenTable->updateAll(
                        ['schedule_id' => null, 'waitlist_order' => null],
                        ['schedule_id IN' => $schedulesInOrg]
                    );
                }

                // 3. Delete schedules
                $this->fetchTable('Schedules')->deleteAll(['organization_id' => $orgId]);

                // 4. Delete sibling groups
                $this->fetchTable('SiblingGroups')->deleteAll(['organization_id' => $orgId]);

                // 5. Delete organization_users entries for this org
                $this->fetchTable('OrganizationUsers')->deleteAll(['organization_id' => $orgId]);

                // 6. Delete the organization
                if (!$this->fetchTable('Organizations')->delete($primaryOrg)) {
                    throw new \RuntimeException('Organization could not be deleted');
                }
            }

            // 7. Delete remaining organization_users for this user (if any other memberships)
            $this->fetchTable('OrganizationUsers')->deleteAll(['user_id' => $id]);

            // 8. Finally delete the user
            if (!$this->Users->delete($user)) {
                throw new \RuntimeException('User could not be deleted');
            }

            $connection->commit();
            $this->Flash->success(__('User and organization deleted successfully.'));

        } catch (\Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollback();
            }
            $this->Flash->error(__('Error deleting: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'index']);
    }
}
