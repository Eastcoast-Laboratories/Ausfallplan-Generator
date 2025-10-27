<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Datasource\FactoryLocator;

/**
 * Admin Dashboard Controller
 * 
 * System administrator dashboard with statistics across all organizations
 */
class DashboardController extends AppController
{
    /**
     * Index method - Admin Dashboard
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['_name' => 'login']);
        }
        
        $user = $identity->getOriginalData();
        if (!$user->isSystemAdmin()) {
            $this->Flash->error(__('Access denied. System admin privileges required.'));
            return $this->redirect(['_name' => 'dashboard']);
        }
        
        // Get table locator
        $locator = FactoryLocator::get('Table');
        
        // Organizations statistics
        $organizationsTable = $locator->get('Organizations');
        $totalOrganizations = $organizationsTable->find()->count();
        $activeOrganizations = $organizationsTable->find()
            ->where(['is_active' => true])
            ->count();
        
        $organizationsList = $organizationsTable->find()
            ->select([
                'id',
                'name',
                'is_active',
                'contact_email',
                'created',
            ])
            ->orderBy(['is_active' => 'DESC', 'name' => 'ASC'])
            ->all();
        
        // Users statistics
        $usersTable = $locator->get('Users');
        $totalUsers = $usersTable->find()->count();
        $activeUsers = $usersTable->find()
            ->where(['is_active' => true])
            ->count();
        $systemAdmins = $usersTable->find()
            ->where(['is_system_admin' => true])
            ->count();
        
        // Children statistics
        $childrenTable = $locator->get('Children');
        $totalChildren = $childrenTable->find()->count();
        $activeChildren = $childrenTable->find()
            ->where(['is_active' => true])
            ->count();
        $integrativeChildren = $childrenTable->find()
            ->where(['is_integrative' => true])
            ->count();
        
        // Children per organization
        $childrenPerOrg = $childrenTable->find()
            ->select([
                'organization_id',
                'total' => $childrenTable->find()->func()->count('*'),
                'active' => $childrenTable->find()->func()->sum(
                    $childrenTable->find()->newExpr('CASE WHEN is_active = 1 THEN 1 ELSE 0 END')
                ),
            ])
            ->group(['organization_id'])
            ->toArray();
        
        // Schedules statistics
        $schedulesTable = $locator->get('Schedules');
        $totalSchedules = $schedulesTable->find()->count();
        $activeSchedules = $schedulesTable->find()
            ->where(['Schedules.ends_on >=' => date('Y-m-d')])
            ->count();
        
        // Schedules per organization
        $schedulesPerOrg = $schedulesTable->find()
            ->select([
                'organization_id',
                'total' => $schedulesTable->find()->func()->count('*'),
            ])
            ->group(['organization_id'])
            ->toArray();
        
        // Sibling Groups statistics
        $siblingGroupsTable = $locator->get('SiblingGroups');
        $totalSiblingGroups = $siblingGroupsTable->find()->count();
        
        // Recent activity - last 10 created children
        $recentChildren = $childrenTable->find()
            ->contain(['Organizations'])
            ->orderBy(['Children.created' => 'DESC'])
            ->limit(10)
            ->all();
        
        // Recent schedules
        $recentSchedules = $schedulesTable->find()
            ->contain(['Organizations'])
            ->orderBy(['Schedules.created' => 'DESC'])
            ->limit(10)
            ->all();
        
        // Organization activity summary
        $orgActivity = [];
        foreach ($organizationsList as $org) {
            $childCount = 0;
            $scheduleCount = 0;
            
            foreach ($childrenPerOrg as $child) {
                if ($child->organization_id == $org->id) {
                    $childCount = $child->total;
                    break;
                }
            }
            
            foreach ($schedulesPerOrg as $schedule) {
                if ($schedule->organization_id == $org->id) {
                    $scheduleCount = $schedule->total;
                    break;
                }
            }
            
            $orgActivity[] = [
                'organization' => $org,
                'children_count' => $childCount,
                'schedules_count' => $scheduleCount,
            ];
        }
        
        $this->set(compact(
            'totalOrganizations',
            'activeOrganizations',
            'totalUsers',
            'activeUsers',
            'systemAdmins',
            'totalChildren',
            'activeChildren',
            'integrativeChildren',
            'totalSchedules',
            'activeSchedules',
            'totalSiblingGroups',
            'recentChildren',
            'recentSchedules',
            'orgActivity'
        ));
    }
}
