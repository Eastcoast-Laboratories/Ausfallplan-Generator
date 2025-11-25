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
        
        // Handle language query parameter (for unauthenticated pages)
        $langParam = $this->request->getQuery('lang');
        if ($langParam && in_array($langParam, ['de', 'en'])) {
            $session = $this->request->getSession();
            $session->write('Config.language', $langParam === 'de' ? 'de_DE' : 'en_US');
            \Cake\I18n\I18n::setLocale($langParam === 'de' ? 'de_DE' : 'en_US');
        } else {
            // Load language from session
            $session = $this->request->getSession();
            $lang = $session->read('Config.language', 'de_DE');
            \Cake\I18n\I18n::setLocale($lang);
        }
        
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

    /**
     * Find siblings assigned to different schedules
     * 
     * @param iterable $children Children with sibling_group_id
     * @param int $currentScheduleId Current schedule ID to compare against
     * @return array Array of missing siblings
     */
    protected function findMissingSiblings($children, int $currentScheduleId): array
    {
        $missingSiblings = [];
        $processedGroups = [];
        
        foreach ($children as $child) {
            if (!$child->sibling_group_id) {
                continue; // No siblings
            }
            
            // Skip if we already processed this sibling group
            if (in_array($child->sibling_group_id, $processedGroups)) {
                continue;
            }
            $processedGroups[] = $child->sibling_group_id;
            
            // Check if there are actually multiple children in this group
            $totalInGroup = $this->fetchTable('Children')->find()
                ->where(['sibling_group_id' => $child->sibling_group_id])
                ->count();
            
            if ($totalInGroup <= 1) {
                continue; // Skip single-child groups
            }
            
            // Find all siblings in this group
            $siblings = $this->fetchTable('Children')->find()
                ->where([
                    'sibling_group_id' => $child->sibling_group_id,
                    'id !=' => $child->id,
                ])
                ->orderBy(['name' => 'ASC'])
                ->all();
            
            foreach ($siblings as $sib) {
                // Show warning if sibling is assigned to a DIFFERENT schedule
                if ($sib->schedule_id != null && 
                    $sib->schedule_id != $currentScheduleId) {
                    $missingSiblings[] = [
                        'id' => $sib->id,
                        'child_id' => $child->id, // Current child ID
                        'sibling_id' => $sib->id, // Missing sibling ID
                        'sibling_group_id' => $child->sibling_group_id,
                        'schedule_id' => $sib->schedule_id, // Sibling's schedule (where they currently are)
                        'name' => $sib->name, // Name of the missing sibling
                        'sibling_of' => $child->name, // Name of the current child
                    ];
                }
            }
        }
        
        return $missingSiblings;
    }
}
