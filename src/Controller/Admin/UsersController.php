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
            ->contain(['Organizations'])
            ->orderBy(['Users.created' => 'DESC'])
            ->all();
        
        $this->set(compact('users'));
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
}
