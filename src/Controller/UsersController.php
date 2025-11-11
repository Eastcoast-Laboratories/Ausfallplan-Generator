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
        
        // Load all organizations for selectbox
        $organizationsTable = $this->fetchTable('Organizations');
        $organizationsList = $organizationsTable->find()
            ->where(['name !=' => 'keine organisation'])
            ->orderBy(['name' => 'ASC'])
            ->all()
            ->combine('id', 'name')
            ->toArray();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Handle organization choice
            $orgChoice = $data['organization_choice'] ?? 'new';
            $organizationsTable = $this->fetchTable('Organizations');
            $isNewOrganization = false;
            
            if ($orgChoice === 'new') {
                // Create new organization
                $organizationName = trim($data['organization_name'] ?? '');
                
                if (empty($organizationName)) {
                    $this->Flash->error(__('Bitte geben Sie einen Namen für die neue Organisation ein.'));
                    $this->set(compact('user', 'organizationsList'));
                    return;
                }
                
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
                        $this->set(compact('user', 'organizationsList'));
                        return;
                    }
                    $isNewOrganization = true;
                } else {
                    // Organization with this name already exists
                    $isNewOrganization = false;
                }
                
                $data['organization_id'] = $organization->id;
            } else {
                // Join existing organization (ID from selectbox)
                $organization = $organizationsTable->get($orgChoice);
                $data['organization_id'] = $organization->id;
                $isNewOrganization = false;
            }
            
            $requestedRole = $data['requested_role'] ?? 'editor';
            
            unset($data['organization_choice']); // Remove the choice field
            unset($data['organization_name']); // Remove the text field, we use organization_id
            unset($data['requested_role']); // Don't save to users table
            
            $user = $this->Users->patchEntity($user, $data);
            
            // Set initial status and email verification
            // For new organization creators (org_admins), auto-verify and activate
            if ($isNewOrganization) {
                $user->status = 'active';
                $user->email_verified = true;
                $user->email_token = null; // No verification needed
            } else {
                // For joining existing organizations, require verification
                $user->status = 'pending';
                $user->email_verified = false;
                $user->email_token = bin2hex(random_bytes(16));
            }
            
            if ($this->Users->save($user)) {
                // Handle encryption: Generate initial wrapped DEK if encryption keys provided
                if (!empty($data['public_key']) && $organization->encryption_enabled) {
                    $encryptedDeksTable = $this->fetchTable('EncryptedDeks');
                    
                    // For new organizations: Generate a new DEK for the organization
                    // For existing organizations: Admin must wrap DEK for new user separately
                    if ($isNewOrganization) {
                        // Generate a random DEK (32 bytes for AES-256)
                        $dek = random_bytes(32);
                        
                        // In real implementation, this would be wrapped with user's public key
                        // For now, we just store a placeholder that indicates encryption is set up
                        $wrappedDek = base64_encode($dek); // Simplified - should use public key encryption
                        
                        $encryptedDeksTable->save($encryptedDeksTable->newEntity([
                            'organization_id' => $organization->id,
                            'user_id' => $user->id,
                            'wrapped_dek' => $wrappedDek,
                        ]));
                    }
                }
                
                // Create organization_users entry
                $orgUsersTable = $this->fetchTable('OrganizationUsers');
                
                // If new organization, user becomes org_admin automatically
                // If existing organization, use requested role
                $roleInOrg = $isNewOrganization ? 'org_admin' : $requestedRole;
                
                $orgUser = $orgUsersTable->newEntity([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'role' => $roleInOrg,
                    'is_primary' => true,
                    'joined_at' => new \DateTime(),
                ]);
                $orgUsersTable->save($orgUser);
                
                // Send verification email to user
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
                
                // If joining existing organization, notify org-admins
                if (!$isNewOrganization && $organization->name !== 'keine organisation') {
                    $this->notifyOrgAdminsAboutNewUser($user, $organization, $roleInOrg);
                }
                
                $debugLink = \Cake\Routing\Router::url(['controller' => 'Debug', 'action' => 'emails'], true);
                
                if ($isNewOrganization) {
                    $message = __('Registration successful! You are the admin of your new organization. Please check your email to verify your account.');
                } else if ($organization->name === 'keine organisation') {
                    $message = __('Registration successful. Please check your email to verify your account.');
                } else {
                    $message = __('Registration successful! Organization admins have been notified and will review your request. Please check your email to verify your account.');
                }
                
                $this->Flash->success(
                    $message . " (Dev: <a href='{$debugLink}' style='color: white; text-decoration: underline;'>View Emails</a>)",
                    ['escape' => false]
                );
                return $this->redirect(['action' => 'login']);
            }
            $this->Flash->error(__('Registration failed. Please try again.'));
        }
        
        $this->set(compact('user', 'organizationsList'));
    }
    
    /**
     * Notify organization admins about new user registration
     */
    private function notifyOrgAdminsAboutNewUser($user, $organization, $requestedRole): void
    {
        // Get all ACTIVE org-admins of the organization (exclude pending/inactive users)
        $orgAdmins = $this->fetchTable('OrganizationUsers')
            ->find()
            ->where([
                'OrganizationUsers.organization_id' => $organization->id,
                'OrganizationUsers.role' => 'org_admin'
            ])
            ->contain(['Users' => function ($q) {
                return $q->where(['Users.status' => 'active']);
            }])
            ->all();
        
        if ($orgAdmins->isEmpty()) {
            return;
        }
        
        // Create approval URL for org-admins
        $approvalUrl = \Cake\Routing\Router::url([
            'controller' => 'Users',
            'action' => 'approveOrgUser',
            $user->id
        ], true);
        
        $roleLabels = [
            'org_admin' => 'Organization Admin',
            'editor' => 'Editor',
            'viewer' => 'Viewer'
        ];
        $roleLabel = $roleLabels[$requestedRole] ?? $requestedRole;
        
        // Send email to each org-admin
        foreach ($orgAdmins as $orgAdmin) {
            // Skip if user doesn't exist or is not active
            if (!$orgAdmin->user || $orgAdmin->user->status !== 'active') {
                continue;
            }
            
            \App\Service\EmailDebugService::send([
                'to' => $orgAdmin->user->email,
                'subject' => "New user registration for {$organization->name}",
                'body' => "Hello,\n\nA new user has registered to join your organization '{$organization->name}'.\n\nUser Details:\n- Email: {$user->email}\n- Requested Role: {$roleLabel}\n\nPlease review and approve this user:\n{$approvalUrl}\n\nIf you did not expect this registration, please contact support.",
                'links' => [
                    'Approve User' => $approvalUrl
                ],
                'data' => [
                    'new_user_id' => $user->id,
                    'new_user_email' => $user->email,
                    'organization_id' => $organization->id,
                    'organization_name' => $organization->name,
                    'requested_role' => $requestedRole,
                    'admin_email' => $orgAdmin->user->email
                ]
            ]);
        }
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
                return $this->redirect(['action' => 'login']);
            }
            
            if ($identity->status !== 'active') {
                $this->Authentication->logout();
                $this->Flash->error(__('Your account is pending approval by an administrator.'));
                return $this->redirect(['action' => 'login']);
            }
            
            // Load encryption keys and wrapped DEKs for client-side decryption
            $user = $this->Users->get($identity->id, [
                'fields' => ['id', 'encrypted_private_key', 'key_salt']
            ]);
            
            if ($user->encrypted_private_key && $user->key_salt) {
                // Load wrapped DEKs for user's organizations
                $encryptedDeksTable = $this->fetchTable('EncryptedDeks');
                $wrappedDeks = $encryptedDeksTable->find()
                    ->where(['user_id' => $user->id])
                    ->contain(['Organizations' => ['fields' => ['id', 'name', 'encryption_enabled']]])
                    ->all()
                    ->toArray();
                
                // Store in session for client-side JavaScript access
                $this->request->getSession()->write('encryption', [
                    'encrypted_private_key' => $user->encrypted_private_key,
                    'key_salt' => $user->key_salt,
                    'wrapped_deks' => array_map(function($dek) {
                        return [
                            'organization_id' => $dek->organization_id,
                            'organization_name' => $dek->organization->name ?? '',
                            'wrapped_dek' => $dek->wrapped_dek,
                        ];
                    }, $wrappedDeks)
                ]);
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
     * Setup encryption for existing users
     *
     * @return \Cake\Http\Response|null Renders view.
     */
    public function setupEncryption()
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        
        if (!$identity) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'message' => 'Not authenticated']));
        }
        
        $data = $this->request->getData();
        
        if (empty($data['public_key']) || empty($data['encrypted_private_key']) || empty($data['key_salt'])) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'message' => 'Missing required fields']));
        }
        
        $user = $this->Users->get($identity->id);
        
        // Check if user already has encryption set up
        if ($user->encrypted_private_key && $user->key_salt) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'message' => 'Encryption already set up']));
        }
        
        $user->public_key = $data['public_key'];
        $user->encrypted_private_key = $data['encrypted_private_key'];
        $user->key_salt = $data['key_salt'];
        
        if ($this->Users->save($user)) {
            // Generate DEKs for user's organizations
            $orgsUsersTable = $this->fetchTable('OrganizationsUsers');
            $userOrgs = $orgsUsersTable->find()
                ->where(['user_id' => $user->id])
                ->contain(['Organizations'])
                ->all();
            
            $encryptedDeksTable = $this->fetchTable('EncryptedDeks');
            
            foreach ($userOrgs as $orgUser) {
                $org = $orgUser->organization;
                
                if (!$org->encryption_enabled) {
                    continue;
                }
                
                // Check if organization already has a DEK
                $existingDek = $encryptedDeksTable->find()
                    ->where(['organization_id' => $org->id])
                    ->first();
                
                if (!$existingDek) {
                    // Organization doesn't have a DEK yet - needs to be generated client-side
                    // For now, we skip this - DEK will be generated when needed
                    continue;
                }
                
                // For existing DEKs, we need another user to wrap it for this user
                // This is a limitation - for now user needs to ask admin to share DEK
                // TODO: Implement DEK sharing workflow
            }
            
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true]));
        }
        
        return $this->response->withType('application/json')
            ->withStringBody(json_encode(['success' => false, 'message' => 'Failed to save encryption keys']));
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
            // Convert to full locale format (de -> de_DE, en -> en_US)
            $locale = $lang === 'de' ? 'de_DE' : 'en_US';
            
            // Write to session in correct format
            $session = $this->request->getSession();
            $session->write('Config.language', $locale);
            
            // Also set locale immediately for current request
            \Cake\I18n\I18n::setLocale($locale);
            
            $this->Flash->success(__('Language changed to {0}', $lang === 'de' ? 'Deutsch' : 'English'));
        }
        
        // Redirect back to where we came from to reload page with new language
        $referer = $this->referer();
        
        // If referer contains /admin/, redirect to admin dashboard
        if ($referer && strpos($referer, '/admin/') !== false) {
            return $this->redirect(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']);
        }
        
        // Otherwise redirect to regular dashboard
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
                if (!empty($data['confirm_password']) && $data['new_password'] !== $data['confirm_password']) {
                    $this->Flash->error(__('Passwords do not match.'));
                    $this->set(compact('userEntity'));
                    return;
                }
                // Only set password if confirmation is also provided
                if (!empty($data['confirm_password'])) {
                    // Validate password complexity
                    if (!$this->Users->validatePasswordComplexity($data['new_password'])) {
                        $this->Flash->error(__('Password must be at least 8 characters and contain at least one number and one letter.'));
                        $this->set(compact('userEntity'));
                        return;
                    }
                    $data['password'] = $data['new_password'];
                }
            }
            
            // Remove password-related fields from data
            unset($data['new_password'], $data['confirm_password']);
            
            // Don't allow role, organization_id, or subscription changes through profile
            unset($data['role'], $data['organization_id'], $data['subscription_plan'], $data['subscription_status']);
            
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
        $this->Authentication->addUnauthenticatedActions(['login', 'register', 'changeLanguage', 'verify', 'forgotPassword', 'resetPassword']);
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
        
        $user->email_verified = 1;
        $user->email_token = null;
        
        // Check if user has an organization
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgUser = $orgUsersTable->find()
            ->where(['user_id' => $user->id, 'is_primary' => true])
            ->contain(['Organizations'])
            ->first();
        
        if ($orgUser && $orgUser->organization) {
            // Count ACTIVE users in this organization (excluding current pending user)
            $activeUserCount = $orgUsersTable->find()
                ->where(['organization_id' => $orgUser->organization_id])
                ->matching('Users', function ($q) {
                    return $q->where(['Users.status' => 'active']);
                })
                ->count();
            
            if ($activeUserCount === 0 || $orgUser->organization->name === 'keine organisation') {
                // First user or "keine organisation" → auto-approve
                $user->status = 'active';
                $user->approved_at = new \DateTime();
                $this->Flash->success(__('E-Mail verifiziert! Sie können sich jetzt anmelden.'));
            } else {
                // Not first user → needs approval
                $user->status = 'pending';
                $this->Flash->info(__('E-Mail verifiziert! Admin-Freigabe erforderlich.'));
            }
        } else {
            // No organization → auto-approve
            $user->status = 'active';
            $user->approved_at = new \DateTime();
            $this->Flash->success(__('E-Mail verifiziert! Sie können sich jetzt anmelden.'));
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
                ->where([
                    'reset_code' => $code,
                    'expires_at >' => new \DateTime(),
                ])
                ->whereNull('used_at')
                ->contain(['Users'])
                ->first();
            
            if ($reset) {
                $user = $reset->user;
                $user->password = $newPassword;
                
                // Handle encryption key re-encryption if provided
                // When password changes, client must re-encrypt private key with new password
                $newEncryptedPrivateKey = $this->request->getData('encrypted_private_key');
                $newKeySalt = $this->request->getData('key_salt');
                
                if ($newEncryptedPrivateKey && $newKeySalt) {
                    $user->encrypted_private_key = $newEncryptedPrivateKey;
                    $user->key_salt = $newKeySalt;
                    // Note: DEKs don't need to be re-wrapped, only the private key changes
                }
                
                $usersTable = $this->fetchTable('Users');
                if ($usersTable->save($user)) {
                    // Mark reset as used - need to mark it as dirty first
                    $resetTable = $this->fetchTable('PasswordResets');
                    $reset->used_at = new \DateTime();
                    $reset->setDirty('used_at', true); // Force CakePHP to update this field
                    
                    if (!$resetTable->save($reset)) {
                        // Log error but don't fail - password was already changed
                        error_log('Failed to mark password reset as used: ' . json_encode($reset->getErrors()));
                    }
                    
                    $this->Flash->success(__('Password reset successful!'));
                    return $this->redirect(['action' => 'login']);
                } else {
                    $this->Flash->error(__('Failed to update password.'));
                }
            } else {
                $this->Flash->error(__('Invalid or expired code.'));
            }
        }
    }
    
    /**
     * Approve organization user
     * Allows org-admins to approve pending users for their organization
     *
     * @param int $userId The user ID to approve
     * @return \Cake\Http\Response|null Redirects to dashboard
     */
    public function approveOrgUser($userId = null)
    {
        // Require login
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Access denied. Please login.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
        
        // Get the user to approve
        $userToApprove = $this->Users->get($userId, [
            'contain' => ['OrganizationUsers' => ['Organizations']]
        ]);
        
        // Check if the logged-in user is an org_admin in any organization that the pending user wants to join
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        
        // Get all organizations where logged-in user is org_admin
        $adminOrgIds = $orgUsersTable->find()
            ->where([
                'user_id' => $identity->id,
                'role' => 'org_admin'
            ])
            ->all()
            ->extract('organization_id')
            ->toList();
        
        if (empty($adminOrgIds)) {
            $this->Flash->error(__('You do not have permission to perform actions. (Viewer role is read-only)'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        
        // Check if user to approve belongs to one of the orgs where current user is admin
        $sharedOrg = null;
        foreach ($userToApprove->organization_users as $orgUser) {
            if (in_array($orgUser->organization_id, $adminOrgIds)) {
                $sharedOrg = $orgUser;
                break;
            }
        }
        
        if (!$sharedOrg) {
            $this->Flash->error(__('You are not an administrator of this user\'s organization.'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        
        // Approve the user
        $userToApprove->status = 'active';
        $userToApprove->approved_at = new \DateTime();
        $userToApprove->approved_by = $identity->id;
        
        if ($this->Users->save($userToApprove)) {
            $this->Flash->success(__('User approved successfully.'));
            
            // Send notification to the approved user
            \App\Service\EmailDebugService::send([
                'to' => $userToApprove->email,
                'subject' => 'Your account has been approved',
                'body' => "Hello,\n\nYour account for organization '{$sharedOrg->organization->name}' has been approved!\n\nYou can now log in and start using FairNestPlan.\n\nLogin: " . \Cake\Routing\Router::url(['controller' => 'Users', 'action' => 'login'], true),
                'links' => [
                    'Login Now' => \Cake\Routing\Router::url(['controller' => 'Users', 'action' => 'login'], true)
                ],
                'data' => [
                    'user_id' => $userToApprove->id,
                    'organization' => $sharedOrg->organization->name
                ]
            ]);
        } else {
            $this->Flash->error(__('Could not approve user.'));
        }
        
        return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
    }
}
