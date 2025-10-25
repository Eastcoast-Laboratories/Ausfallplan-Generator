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
            
            $this->set(compact('stats', 'user'));
            return;
        }
        
        // Get user's primary organization
        $primaryOrg = $this->getPrimaryOrganization();
        if (!$primaryOrg) {
            $this->Flash->error(__('Sie sind keiner Organisation zugeordnet. Bitte kontaktieren Sie den Administrator.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'logout']);
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
        
        $this->set(compact('stats', 'user'));
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
