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
        $result = $this->Authentication->getResult();
        
        // If user is already logged in or just logged in successfully, redirect
        if ($result && $result->isValid()) {
            // Redirect to dashboard after successful login
            $target = $this->Authentication->getLoginRedirect() ?? ['controller' => 'Dashboard', 'action' => 'index'];
            return $this->redirect($target);
        }
        
        // If POST request but login failed, show error
        if ($this->request->is('post') && !$result->isValid()) {
            $this->Flash->error(__('Invalid email or password'));
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
     * Change language - Switch between German and English
     *
     * @param string|null $lang Language code (de or en)
     * @return \Cake\Http\Response|null|void
     */
    public function changeLanguage(?string $lang = null)
    {
        $validLanguages = ['de', 'en'];
        
        if ($lang && in_array($lang, $validLanguages)) {
            // Write to session
            $session = $this->request->getSession();
            $session->write('Config.language', $lang);
            
            // Also set locale immediately for current request
            \Cake\I18n\I18n::setLocale($lang === 'de' ? 'de_DE' : 'en_US');
            
            $this->Flash->success(__('Language changed to {0}', $lang === 'de' ? 'Deutsch' : 'English'));
        }
        
        // Redirect back to where we came from to reload page with new language
        return $this->redirect($this->referer(['controller' => 'Dashboard', 'action' => 'index']));
    }

    /**
     * Profile settings - Edit user details
     *
     * @return \Cake\Http\Response|null|void
     */
    public function profile()
    {
        $user = $this->Authentication->getIdentity();
        $userEntity = $this->Users->get($user->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            
            // Handle password change
            if (!empty($data['new_password'])) {
                if ($data['new_password'] !== $data['confirm_password']) {
                    $this->Flash->error(__('Passwords do not match.'));
                    $this->set(compact('userEntity'));
                    return;
                }
                $data['password'] = $data['new_password'];
            }
            
            // Remove password-related fields from data
            unset($data['new_password'], $data['confirm_password']);
            
            // Don't allow role or organization_id change through profile
            unset($data['role'], $data['organization_id']);
            
            $userEntity = $this->Users->patchEntity($userEntity, $data);
            
            if ($this->Users->save($userEntity)) {
                $this->Flash->success(__('Your profile has been updated.'));
                return $this->redirect(['action' => 'profile']);
            }
            $this->Flash->error(__('Unable to update your profile.'));
        }

        $this->set(compact('userEntity'));
    }

    /**
     * Account settings - Same as profile for now
     *
     * @return \Cake\Http\Response|null|void
     */
    public function account()
    {
        return $this->redirect(['action' => 'profile']);
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
        
        // Allow public access to register and login
        $this->Authentication->addUnauthenticatedActions(['login', 'register']);
    }
}
