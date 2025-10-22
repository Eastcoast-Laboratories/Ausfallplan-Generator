<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Children Controller
 *
 * @property \App\Model\Table\ChildrenTable $Children
 */
class ChildrenController extends AppController
{
    /**
     * Index method - List all children
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        // Get current user's organization
        $user = $this->Authentication->getIdentity();
        
        $children = $this->Children->find()
            ->where(['Children.organization_id' => $user->organization_id])
            ->contain(['Organizations', 'SiblingGroups'])
            ->orderBy(['Children.is_active' => 'DESC', 'Children.name' => 'ASC'])
            ->all();

        $this->set(compact('children'));
    }

    /**
     * View method - Display a single child
     *
     * @param string|null $id Child id.
     * @return \Cake\Http\Response|null|void
     */
    public function view($id = null)
    {
        $child = $this->Children->get($id, contain: [
            'Organizations',
            'SiblingGroups',
            'Assignments',
            'WaitlistEntries',
        ]);

        $this->set(compact('child'));
    }

    /**
     * Add method - Create a new child
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add()
    {
        $child = $this->Children->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Set organization from authenticated user
            $user = $this->Authentication->getIdentity();
            $data['organization_id'] = $user->organization_id;
            
            // Set defaults
            if (!isset($data['is_active'])) {
                $data['is_active'] = true;
            }
            if (!isset($data['is_integrative'])) {
                $data['is_integrative'] = false;
            }
            
            $child = $this->Children->patchEntity($child, $data);
            
            if ($this->Children->save($child)) {
                $this->Flash->success(__('The child has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The child could not be saved. Please try again.'));
        }
        
        $siblingGroups = $this->Children->SiblingGroups->find('list')
            ->where(['SiblingGroups.organization_id' => $this->Authentication->getIdentity()->organization_id])
            ->all();
        
        $this->set(compact('child', 'siblingGroups'));
    }

    /**
     * Edit method - Update an existing child
     *
     * @param string|null $id Child id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     */
    public function edit($id = null)
    {
        $child = $this->Children->get($id, contain: []);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $child = $this->Children->patchEntity($child, $this->request->getData());
            
            if ($this->Children->save($child)) {
                $this->Flash->success(__('The child has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The child could not be updated. Please try again.'));
        }
        
        $siblingGroups = $this->Children->SiblingGroups->find('list')
            ->where(['SiblingGroups.organization_id' => $this->Authentication->getIdentity()->organization_id])
            ->all();
        
        $this->set(compact('child', 'siblingGroups'));
    }

    /**
     * Delete method - Remove a child
     *
     * @param string|null $id Child id.
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $child = $this->Children->get($id);

        if ($this->Children->delete($child)) {
            $this->Flash->success(__('The child has been deleted.'));
        } else {
            $this->Flash->error(__('The child could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
