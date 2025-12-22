<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Dashboard Controller
 *
 * Main landing page for authenticated users
 */
class DashboardController extends AppController
{
    /**
     * Index method - Dashboard overview
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $user = $this->Authentication->getIdentity();
        
        // Load actual stats from database
        $childrenTable = $this->fetchTable('Children');
        $schedulesTable = $this->fetchTable('Schedules');
        
        // System admins see global stats across all organizations
        if ($user && $user->is_system_admin) {
            $stats = [
                'children' => $childrenTable->find()->count(),
                'schedules' => $schedulesTable->find()->count(),
                'active_schedules' => $schedulesTable->find()
                    ->where([
                        'OR' => [
                            ['ends_on IS' => null],
                            ['ends_on >=' => date('Y-m-d')]
                        ]
                    ])
                    ->count(),
                'waitlist_entries' => 0, // TODO: Implement when waitlist is added
            ];
            
            // Get recent activities for system admin (all organizations)
            $recentActivities = $this->getRecentActivities(null);
            
            $this->set(compact('stats', 'user', 'recentActivities'));
            return;
        }
        
        // Get user's primary organization
        $primaryOrg = $this->getPrimaryOrganization();
        if (!$primaryOrg) {
            // User has no organization - redirect to organizations page to create one
            $this->Flash->info(__('Sie sind noch keiner Organisation zugeordnet. Bitte erstellen Sie eine Organisation.'));
            return $this->redirect(['controller' => 'Admin/Organizations', 'action' => 'index']);
        }
        
        // Get user's role in primary organization
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgUser = $orgUsersTable->find()
            ->where(['user_id' => $user->id, 'organization_id' => $primaryOrg->id])
            ->first();
        
        $userRole = $orgUser ? $orgUser->role : null;
        $isEditor = $userRole === 'editor' || $userRole === 'org_admin';
        
        // Check if user has any schedules
        $scheduleCount = $schedulesTable->find()
            ->where(['Schedules.organization_id' => $primaryOrg->id])
            ->count();
        
        // If no schedules exist and user is editor, redirect to schedule add
        if ($scheduleCount === 0 && $isEditor) {
            $this->Flash->info(__('No schedules found. Please create a schedule to get started.'));
            return $this->redirect(['controller' => 'Schedules', 'action' => 'add']);
        }
        
        // Find schedule with highest capacity_per_day
        $maxCapacitySchedule = $schedulesTable->find()
            ->where(['Schedules.organization_id' => $primaryOrg->id])
            ->orderBy(['Schedules.capacity_per_day' => 'DESC'])
            ->first();
        
        // Check if children count is less than max capacity (only for editors)
        if ($maxCapacitySchedule && $isEditor) {
            $childrenCount = $childrenTable->find()
                ->where(['Children.organization_id' => $primaryOrg->id])
                ->count();
            
            if ($childrenCount < $maxCapacitySchedule->capacity_per_day) {
                $minRequired = $maxCapacitySchedule->capacity_per_day;
                $this->Flash->info(__('You need at least {0} children to complete your schedule. Please add more children.', $minRequired));
                $this->request->getSession()->write('activeScheduleId', (int)$maxCapacitySchedule->id);
                return $this->redirect(['controller' => 'Children', 'action' => 'add']);
            }
        }
        
        // Regular users see their organization stats
        $stats = [
            'children' => $childrenTable->find()
                ->where(['Children.organization_id' => $primaryOrg->id])
                ->count(),
            'schedules' => $schedulesTable->find()
                ->where(['Schedules.organization_id' => $primaryOrg->id])
                ->count(),
            'active_schedules' => $schedulesTable->find()
                ->where([
                    'Schedules.organization_id' => $primaryOrg->id,
                    'OR' => [
                        ['ends_on IS' => null],
                        ['ends_on >=' => date('Y-m-d')]
                    ]
                ])
                ->count(),
            'waitlist_entries' => 0, // TODO: Implement when waitlist is added
        ];
        
        // Get recent activities for this organization
        $recentActivities = $this->getRecentActivities($primaryOrg->id);
        
        // Check if user is viewer
        $isViewer = ($userRole === 'viewer');
        
        $this->set(compact('stats', 'user', 'recentActivities', 'isViewer'));
    }
    
    /**
     * Get recent activities (children and schedules)
     *
     * @param int|null $organizationId Organization ID or null for all
     * @return array Recent activities
     */
    private function getRecentActivities(?int $organizationId = null): array
    {
        $activities = [];
        
        // Get recent children (include encryption fields for client-side decryption)
        $childrenTable = $this->fetchTable('Children');
        $childrenQuery = $childrenTable->find()
            ->contain(['Organizations'])
            ->select([
                'Children.id',
                'Children.name',
                'Children.name_encrypted',
                'Children.name_iv',
                'Children.name_tag',
                'Children.organization_id',
                'Children.created',
            ])
            ->order(['Children.created' => 'DESC'])
            ->limit(5);
            
        if ($organizationId !== null) {
            $childrenQuery->where(['Children.organization_id' => $organizationId]);
        }
        
        foreach ($childrenQuery as $child) {
            $activities[] = [
                'type' => 'child',
                'icon' => '👶',
                'title_prefix' => __('Child added:'),
                'name' => $child->name,
                'name_encrypted' => $child->name_encrypted,
                'name_iv' => $child->name_iv,
                'name_tag' => $child->name_tag,
                'organization' => $child->organization->name ?? '-',
                'organization_id' => $child->organization_id,
                'time' => $child->created,
                'url' => ['controller' => 'Children', 'action' => 'view', $child->id],
            ];
        }
        
        // Get recent schedules
        $schedulesTable = $this->fetchTable('Schedules');
        $schedulesQuery = $schedulesTable->find()
            ->contain(['Organizations'])
            ->order(['Schedules.created' => 'DESC'])
            ->limit(5);
            
        if ($organizationId !== null) {
            $schedulesQuery->where(['Schedules.organization_id' => $organizationId]);
        }
        
        foreach ($schedulesQuery as $schedule) {
            $activities[] = [
                'type' => 'schedule',
                'icon' => '📅',
                'title' => __('Schedule created: {0}', $schedule->title),
                'organization' => $schedule->organization->name ?? '-',
                'time' => $schedule->created,
                'url' => ['controller' => 'Schedules', 'action' => 'view', $schedule->id]
            ];
        }
        
        // Sort by time (most recent first)
        usort($activities, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        // Return top 10
        return array_slice($activities, 0, 10);
    }

    /**
     * beforeFilter callback
     *
     * @param \Cake\Event\EventInterface $event Event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Dashboard requires authentication (no unauthenticated actions)
    }
}
