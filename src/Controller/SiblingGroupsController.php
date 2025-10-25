<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * SiblingGroups Controller
 *
 * @property \App\Model\Table\SiblingGroupsTable $SiblingGroups
 */
class SiblingGroupsController extends AppController
{
    /**
     * Index method - List all sibling groups
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        // Get current user's organization
        $user = $this->Authentication->getIdentity();
        
        // System admins can see all sibling groups
        if ($user && $user->is_system_admin) {
            $siblingGroups = $this->SiblingGroups->find()
                ->contain(['Organizations', 'Children'])
                ->orderBy(['SiblingGroups.label' => 'ASC'])
                ->all();
            $this->set(compact('siblingGroups'));
            return;
        }
        
        // Get user's primary organization
        $primaryOrg = $this->getPrimaryOrganization();
        if (!$primaryOrg) {
            $this->Flash->error(__('Sie sind keiner Organisation zugeordnet.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'logout']);
        }
        
        $siblingGroups = $this->SiblingGroups->find()
            ->where(['SiblingGroups.organization_id' => $primaryOrg->id])
            ->contain(['Organizations', 'Children'])
            ->orderBy(['SiblingGroups.label' => 'ASC'])
            ->all();

        $this->set(compact('siblingGroups'));
    }

    /**
     * View method - Display a single sibling group
     *
     * @param string|null $id Sibling Group id.
     * @return \Cake\Http\Response|null|void
     */
    public function view($id = null)
    {
        $siblingGroup = $this->SiblingGroups->get($id, contain: [
            'Organizations',
            'Children',
        ]);

        $this->set(compact('siblingGroup'));
    }

    /**
     * Add method - Create a new sibling group
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add()
    {
        $siblingGroup = $this->SiblingGroups->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Set organization from authenticated user
            $user = $this->Authentication->getIdentity();
            $data['organization_id'] = $user->organization_id;
            
            $siblingGroup = $this->SiblingGroups->patchEntity($siblingGroup, $data);
            
            if ($this->SiblingGroups->save($siblingGroup)) {
                $this->Flash->success(__('The sibling group has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The sibling group could not be saved. Please try again.'));
        }
        
        $this->set(compact('siblingGroup'));
    }

    /**
     * Edit method - Update an existing sibling group
     *
     * @param string|null $id Sibling Group id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     */
    public function edit($id = null)
    {
        $siblingGroup = $this->SiblingGroups->get($id, contain: []);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $siblingGroup = $this->SiblingGroups->patchEntity($siblingGroup, $this->request->getData());
            
            if ($this->SiblingGroups->save($siblingGroup)) {
                $this->Flash->success(__('The sibling group has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The sibling group could not be updated. Please try again.'));
        }
        
        $this->set(compact('siblingGroup'));
    }

    /**
     * Delete method - Remove a sibling group
     *
     * @param string|null $id Sibling Group id.
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $siblingGroup = $this->SiblingGroups->get($id, contain: ['Children']);

        // Check if group has children
        if (count($siblingGroup->children) > 0) {
            $this->Flash->error(__('Cannot delete sibling group with children. Please remove children from this group first.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($this->SiblingGroups->delete($siblingGroup)) {
            $this->Flash->success(__('The sibling group has been deleted.'));
        } else {
            $this->Flash->error(__('The sibling group could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
