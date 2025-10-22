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
        
        // Get stats for dashboard
        $stats = [
            'children' => 0,
            'schedules' => 0,
            'active_schedules' => 0,
            'waitlist_entries' => 0,
        ];
        
        // TODO: Load actual stats from database when tables are ready
        // $stats['children'] = $this->fetchTable('Children')->find()->where(['organization_id' => $user->organization_id])->count();
        // $stats['schedules'] = $this->fetchTable('Schedules')->find()->where(['organization_id' => $user->organization_id])->count();
        
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
