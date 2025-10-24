<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;

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
                // Check if organization already exists, otherwise create new one
                $organizationsTable = $this->fetchTable('Organizations');
                $organization = $organizationsTable->find()
                    ->where(['name' => $organizationName])
                    ->first();
                
                if (!$organization) {
                    // Create new organization with creator's email as contact
                    $organization = $organizationsTable->newEntity([
                        'name' => $organizationName,
                        'contact_email' => $data['email']
                    ]);
                    
                    if (!$organizationsTable->save($organization)) {
                        $this->Flash->error(__('Could not create organization.'));
                        $this->set(compact('user'));
                        return;
                    }
                }
                
                $data['organization_id'] = $organization->id;
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
            
            // Set initial status and email verification
            $user->status = 'pending';
            $user->email_verified = false;
            $user->email_token = bin2hex(random_bytes(16));
            
            if ($this->Users->save($user)) {
                // Send verification email (or store for debug on localhost)
                $verifyUrl = \Cake\Routing\Router::url([
                    'controller' => 'Users',
                    'action' => 'verify',
                    $user->email_token
                ], true);
                
                \App\Service\EmailDebugService::send([
                    'to' => $user->email,
                    'subject' => 'Verify your email address',
                    'body' => "Hello,\n\nPlease verify your email address by clicking the link below:\n\n{$verifyUrl}\n\nIf you did not register, please ignore this email.",
                    'links' => [
                        'Verify Email' => $verifyUrl
                    ],
                    'data' => [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'token' => $user->email_token
                    ]
                ]);
                
                $debugLink = \Cake\Routing\Router::url(['controller' => 'Debug', 'action' => 'emails'], true);
                $this->Flash->success(
                    __('Registration successful. Please check your email to verify your account.') . 
                    " (Dev: <a href='{$debugLink}' style='color: white; text-decoration: underline;'>View Emails</a>)",
                    ['escape' => false]
                );
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
            // Check email verification and account status
            $identity = $this->Authentication->getIdentity();
            
            if (!$identity->email_verified) {
                $this->Authentication->logout();
                $this->Flash->error(__('Please verify your email before logging in.'));
                return;
            }
            
            if ($identity->status !== 'active') {
                $this->Authentication->logout();
                $this->Flash->error(__('Your account is pending approval by an administrator.'));
                return;
            }
            
            // Get redirect target from query parameter or use dashboard as fallback
            $redirect = $this->request->getQuery('redirect');
            
            if ($redirect) {
                // Redirect to requested URL
                return $this->redirect($redirect);
            }
            
            // Default: redirect to dashboard
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
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
        
        // Allow public access to register, login, and password recovery
        $this->Authentication->addUnauthenticatedActions(['login', 'register', 'setLanguage', 'verify', 'forgotPassword', 'resetPassword']);
    }

    public function verify($token = null)
    {
        // Allow verification by email query parameter for testing (when debug routes are enabled)
        $emailParam = $this->request->getQuery('email');
        if ($emailParam && Configure::read('allowDebugRoutes')) {
            $user = $this->Users->find()->where(['email' => $emailParam])->first();
            if ($user && !$user->email_verified) {
                $token = $user->email_token;
            }
        }
        
        if (!$token) {
            $this->Flash->error(__('Invalid verification link.'));
            return $this->redirect(['action' => 'login']);
        }
        
        $user = $this->Users->find()->where(['email_token' => $token])->first();
        if (!$user) {
            $this->Flash->error(__('Invalid token.'));
            return $this->redirect(['action' => 'login']);
        }
        
        $user->email_verified = true;
        $user->email_token = null;
        
        $org = $this->fetchTable('Organizations')->get($user->organization_id);
        $userCount = $this->Users->find()->where(['organization_id' => $user->organization_id])->count();
        
        if ($userCount === 1 || $org->name === 'keine organisation') {
            $user->status = 'active';
            $user->approved_at = new \DateTime();
            $this->Flash->success(__('E-Mail verifiziert! Sie können sich jetzt anmelden.'));
        } else {
            $user->status = 'pending';
            $this->Flash->info(__('E-Mail verifiziert! Admin-Freigabe erforderlich.'));
        }
        
        $this->Users->save($user);
        return $this->redirect(['action' => 'login']);
    }

    public function forgotPassword()
    {
        $this->request->allowMethod(['get', 'post']);
        
        if ($this->request->is('post')) {
            $email = $this->request->getData('email');
            $user = $this->Users->find()->where(['email' => $email])->first();
            
            if ($user) {
                $resetCode = str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $reset = $this->fetchTable('PasswordResets')->newEntity([
                    'user_id' => $user->id,
                    'reset_token' => bin2hex(random_bytes(16)),
                    'reset_code' => $resetCode,
                    'expires_at' => new \DateTime('+1 hour'),
                ]);
                
                if ($this->fetchTable('PasswordResets')->save($reset)) {
                    // Send password reset email (or store for debug on localhost)
                    \App\Service\EmailDebugService::send([
                        'to' => $user->email,
                        'subject' => 'Password Reset Code',
                        'body' => "Hello,\n\nYour password reset code is: {$resetCode}\n\nThis code will expire in 1 hour.\n\nIf you did not request a password reset, please ignore this email.",
                        'links' => [
                            'Reset Password' => \Cake\Routing\Router::url(['controller' => 'Users', 'action' => 'resetPassword'], true)
                        ],
                        'data' => [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'code' => $resetCode
                        ]
                    ]);
                    
                    $this->Flash->success(__('Reset code sent. Check your email.'));
                    return $this->redirect(['action' => 'resetPassword']);
                }
            }
            $this->Flash->success(__('If email exists, reset sent.'));
            return $this->redirect(['action' => 'resetPassword']);
        }
    }

    public function resetPassword()
    {
        $this->request->allowMethod(['get', 'post']);
        
        if ($this->request->is('post')) {
            $code = $this->request->getData('code');
            $newPassword = $this->request->getData('new_password');
            
            $reset = $this->fetchTable('PasswordResets')->find()
                ->where(['reset_code' => $code, 'expires_at >' => new \DateTime(), 'used_at IS' => null])
                ->contain(['Users'])
                ->first();
            
            if ($reset) {
                $user = $reset->user;
                $user->password = $newPassword;
                
                if ($this->Users->save($user)) {
                    $reset->used_at = new \DateTime();
                    $this->fetchTable('PasswordResets')->save($reset);
                    $this->Flash->success(__('Password reset successful!'));
                    return $this->redirect(['action' => 'login']);
                }
            }
            $this->Flash->error(__('Invalid or expired code.'));
        }
    }
}
