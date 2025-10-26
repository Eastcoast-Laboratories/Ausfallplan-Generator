<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * beforeFilter callback - Set layout based on authentication
     *
     * @param \Cake\Event\EventInterface $event Event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Use authenticated layout for logged-in users
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            $this->viewBuilder()->setLayout('authenticated');
        }
    }

    /**
     * Check if user has role in organization
     *
     * @param int $organizationId Organization ID
     * @param string|null $role Required role (viewer, editor, org_admin) or null for membership check
     * @return bool True if user has permission
     */
    protected function hasOrgRole(int $organizationId, ?string $role = null): bool
    {
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            return false;
        }
        
        // System admin has access to everything
        if ($user->is_system_admin) {
            return true;
        }
        
        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->where([
                'user_id' => $user->id,
                'organization_id' => $organizationId
            ])
            ->first();
        
        if (!$orgUser) {
            return false;
        }
        
        if ($role === null) {
            return true; // Just check membership
        }
        
        // Role hierarchy: org_admin > editor > viewer
        $hierarchy = ['viewer' => 1, 'editor' => 2, 'org_admin' => 3];
        return isset($hierarchy[$orgUser->role]) && 
               isset($hierarchy[$role]) && 
               $hierarchy[$orgUser->role] >= $hierarchy[$role];
    }

    /**
     * Get user's organizations
     *
     * @return array List of organizations the user is a member of
     */
    protected function getUserOrganizations(): array
    {
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            return [];
        }
        
        // System admin sees all organizations
        if ($user->is_system_admin) {
            return $this->fetchTable('Organizations')->find()->all()->toArray();
        }
        
        // Get organizations through join table
        $orgUsers = $this->fetchTable('OrganizationUsers')
            ->find()
            ->where(['user_id' => $user->id])
            ->contain(['Organizations'])
            ->all();
        
        return collection($orgUsers)
            ->extract('organization')
            ->toArray();
    }

    /**
     * Get user's primary organization
     *
     * @return \App\Model\Entity\Organization|null Primary organization or first organization
     */
    protected function getPrimaryOrganization(): ?\App\Model\Entity\Organization
    {
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            return null;
        }
        
        // Try to find primary organization (works for both system admins and regular users)
        $orgUser = $this->fetchTable('OrganizationUsers')
            ->find()
            ->where([
                'user_id' => $user->id,
                'is_primary' => true
            ])
            ->contain(['Organizations'])
            ->first();
        
        if ($orgUser && $orgUser->organization) {
            return $orgUser->organization;
        }
        
        // Fallback: return first organization
        $orgUser = $this->fetchTable('OrganizationUsers')
            ->find()
            ->where(['user_id' => $user->id])
            ->contain(['Organizations'])
            ->first();
        
        return $orgUser ? $orgUser->organization : null;
    }

    /**
     * Get user's role in organization
     *
     * @param int $organizationId Organization ID
     * @return string|null Role name (org_admin, editor, viewer) or null if not a member
     */
    protected function getUserRoleInOrg(int $organizationId): ?string
    {
        $user = $this->Authentication->getIdentity();
        
        if (!$user) {
            return null;
        }
        
        // System admin is like org_admin everywhere
        if ($user->is_system_admin) {
            return 'org_admin';
        }
        
        $orgUser = $this->fetchTable('OrganizationUsers')
            ->find()
            ->where([
                'user_id' => $user->id,
                'organization_id' => $organizationId
            ])
            ->first();
        
        return $orgUser ? $orgUser->role : null;
    }
}
