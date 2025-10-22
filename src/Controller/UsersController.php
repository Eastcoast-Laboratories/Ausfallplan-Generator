<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * Register method - Create a new user account
     *
     * @return \Cake\Http\Response|null|void Redirects on successful registration
     */
    public function register()
    {
        $user = $this->Users->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            
            // Set default role to 'viewer' if not specified
            if (empty($user->role)) {
                $user->role = 'viewer';
            }
            
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Your account has been created. Please check your email to verify your account.'));
                return $this->redirect(['action' => 'login']);
            }
            
            $this->Flash->error(__('Unable to create your account. Please try again.'));
        }
        
        $organizations = $this->Users->Organizations->find('list', limit: 200)->all();
        $this->set(compact('user', 'organizations'));
    }

    /**
     * Login method - Authenticate user
     *
     * @return \Cake\Http\Response|null|void
     */
    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        
        // Check if Authentication component is loaded
        if (isset($this->Authentication)) {
            $result = $this->Authentication->getResult();
            
            // If user is already logged in, redirect
            if ($result && $result->isValid()) {
                $target = $this->Authentication->getLoginRedirect() ?? '/';
                return $this->redirect($target);
            }
            
            if ($this->request->is('post') && !$result->isValid()) {
                $this->Flash->error(__('Invalid email or password'));
            }
        } else {
            // Temporary: Show info that authentication is not yet configured
            if ($this->request->is('post')) {
                $this->Flash->warning(__('Authentication system is not yet configured. Please use the registration form for now.'));
            }
        }
    }

    /**
     * Logout method - End user session
     *
     * @return \Cake\Http\Response|null|void
     */
    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            $this->Authentication->logout();
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
    }

    /**
     * beforeFilter callback - Allow public access to register and login
     *
     * @param \Cake\Event\EventInterface $event Event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Allow public access to register and login (if Authentication component is loaded)
        if (isset($this->Authentication)) {
            $this->Authentication->addUnauthenticatedActions(['login', 'register']);
        }
    }
}
