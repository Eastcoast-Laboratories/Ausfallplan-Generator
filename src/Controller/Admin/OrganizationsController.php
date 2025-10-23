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

        $organization = $this->Organizations->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $organization = $this->Organizations->patchEntity($organization, $this->request->getData());
            
            if ($this->Organizations->save($organization)) {
                $this->Flash->success(__('The organization has been saved.'));
                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__('The organization could not be saved. Please, try again.'));
        }

        $this->set(compact('organization'));
    }

    /**
     * Delete method - Delete organization (only if no users)
     *
     * @param string|null $id Organization id
     * @return \Cake\Http\Response|null
     */
    public function delete($id = null)
    {
        $user = $this->Authentication->getIdentity();
        if ($user->role !== 'admin') {
            $this->Flash->error(__('Access denied.'));
            return $this->redirect(['_name' => 'dashboard']);
        }

        $this->request->allowMethod(['post', 'delete']);
        $organization = $this->Organizations->get($id, ['contain' => ['Users']]);

        // Check if organization has users
        if (count($organization->users) > 0) {
            $this->Flash->error(__('Cannot delete organization with active users.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($this->Organizations->delete($organization)) {
            $this->Flash->success(__('The organization has been deleted.'));
        } else {
            $this->Flash->error(__('The organization could not be deleted. Please, try again.'));
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
}
