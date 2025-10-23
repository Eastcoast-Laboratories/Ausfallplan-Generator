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
            $data = $this->request->getData();
            
            // Handle organization: create new or use "keine organisation"
            $organizationName = trim($data['organization_name'] ?? '');
            
            if (!empty($organizationName)) {
                // Create new organization
                $organizationsTable = $this->fetchTable('Organizations');
                $organization = $organizationsTable->newEntity([
                    'name' => $organizationName
                ]);
                
                if ($organizationsTable->save($organization)) {
                    $data['organization_id'] = $organization->id;
                } else {
                    $this->Flash->error(__('Could not create organization.'));
                    $this->set(compact('user'));
                    return;
                }
            } else {
                // Use "keine organisation"
                $organizationsTable = $this->fetchTable('Organizations');
                $noOrg = $organizationsTable->find()
                    ->where(['name' => 'keine organisation'])
                    ->first();
                
                if (!$noOrg) {
                    // Create "keine organisation" if it doesn't exist
                    $noOrg = $organizationsTable->newEntity(['name' => 'keine organisation']);
                    $organizationsTable->save($noOrg);
                }
                
                $data['organization_id'] = $noOrg->id;
            }
            
            unset($data['organization_name']); // Remove the text field, we use organization_id
            
            $user = $this->Users->patchEntity($user, $data);
            
            // Set default role to 'viewer' if not specified
            if (empty($user->role)) {
                $user->role = 'viewer';
            }
            
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Registration successful. Please login.'));
                return $this->redirect(['action' => 'login']);
            }
            $this->Flash->error(__('Registration failed. Please try again.'));
        }
        
        $this->set(compact('user'));
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
            // Get redirect target from authentication or fallback to dashboard
            $target = $this->Authentication->getLoginRedirect();
            
            // If no redirect target, explicitly set dashboard
            if (!$target) {
                $target = ['controller' => 'Dashboard', 'action' => 'index'];
            }
            
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
