<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Schedules Controller
 *
 * @property \App\Model\Table\SchedulesTable $Schedules
 */
class SchedulesController extends AppController
{
    /**
     * Index method - List all schedules
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        // Get current user's organization
        $user = $this->Authentication->getIdentity();
        
        $schedules = $this->Schedules->find()
            ->where(['Schedules.organization_id' => $user->organization_id])
            ->contain(['Organizations'])
            ->orderBy(['Schedules.created' => 'DESC'])
            ->all();

        $this->set(compact('schedules'));
    }

    /**
     * View method - Display a single schedule
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void
     */
    public function view($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: ['Organizations', 'ScheduleDays']);

        $this->set(compact('schedule'));
    }

    /**
     * Add method - Create a new schedule
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add()
    {
        $schedule = $this->Schedules->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Set organization from authenticated user
            $user = $this->Authentication->getIdentity();
            $data['organization_id'] = $user->organization_id;
            
            // Set default state to 'draft'
            if (empty($data['state'])) {
                $data['state'] = 'draft';
            }
            
            $schedule = $this->Schedules->patchEntity($schedule, $data);
            
            if ($this->Schedules->save($schedule)) {
                $this->Flash->success(__('The schedule has been created.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The schedule could not be saved. Please try again.'));
        }
        
        $this->set(compact('schedule'));
    }

    /**
     * Edit method - Update an existing schedule
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     */
    public function edit($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: []);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $schedule = $this->Schedules->patchEntity($schedule, $this->request->getData());
            
            if ($this->Schedules->save($schedule)) {
                $this->Flash->success(__('The schedule has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The schedule could not be updated. Please try again.'));
        }
        
        $this->set(compact('schedule'));
    }

    /**
     * Delete method - Remove a schedule
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $schedule = $this->Schedules->get($id);

        if ($this->Schedules->delete($schedule)) {
            $this->Flash->success(__('The schedule has been deleted.'));
        } else {
            $this->Flash->error(__('The schedule could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Manage Children method - Assign/remove children to/from schedule
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void
     */
    public function manageChildren($id = null)
    {
        $schedule = $this->Schedules->get($id);
        
        // Get all children in this schedule (via assignments)
        $assignedChildrenIds = $this->fetchTable('Assignments')->find()
            ->select(['child_id' => 'DISTINCT Assignments.child_id'])
            ->innerJoinWith('ScheduleDays')
            ->where(['ScheduleDays.schedule_id' => $schedule->id])
            ->all()
            ->extract('child_id')
            ->toArray();
        
        // Get assigned children details
        $assignedChildren = [];
        if (!empty($assignedChildrenIds)) {
            $assignedChildren = $this->fetchTable('Children')->find()
                ->where(['Children.id IN' => $assignedChildrenIds])
                ->orderBy(['Children.name' => 'ASC'])
                ->all();
        }
        
        // Get available children (not yet assigned)
        $user = $this->Authentication->getIdentity();
        $availableChildrenQuery = $this->fetchTable('Children')->find()
            ->where([
                'Children.organization_id' => $user->organization_id,
                'Children.is_active' => true,
            ])
            ->orderBy(['Children.name' => 'ASC']);
        
        if (!empty($assignedChildrenIds)) {
            $availableChildrenQuery->where([
                'Children.id NOT IN' => $assignedChildrenIds
            ]);
        }
        
        $availableChildren = $availableChildrenQuery->all();
        
        $this->set(compact('schedule', 'assignedChildren', 'availableChildren'));
    }

    /**
     * Add child to schedule - Creates assignment
     *
     * @return \Cake\Http\Response|null Redirects back
     */
    public function assignChild()
    {
        $this->request->allowMethod(['post']);
        
        $data = $this->request->getData();
        $scheduleId = $data['schedule_id'];
        $childId = $data['child_id'];
        
        // Get or create first schedule day for this schedule
        $scheduleDaysTable = $this->fetchTable('ScheduleDays');
        $scheduleDay = $scheduleDaysTable->find()
            ->where(['schedule_id' => $scheduleId])
            ->first();
        
        if (!$scheduleDay) {
            // Create default schedule day
            $scheduleDay = $scheduleDaysTable->newEntity([
                'schedule_id' => $scheduleId,
                'title' => 'Default Day',
                'position' => 1,
                'capacity' => 9,
            ]);
            $scheduleDaysTable->save($scheduleDay);
        }
        
        // Create assignment
        $assignment = $this->fetchTable('Assignments')->newEntity([
            'schedule_day_id' => $scheduleDay->id,
            'child_id' => $childId,
            'weight' => 1,
            'source' => 'manual',
        ]);
        
        if ($this->fetchTable('Assignments')->save($assignment)) {
            $this->Flash->success(__('Child assigned to schedule.'));
        } else {
            $this->Flash->error(__('Could not assign child to schedule.'));
        }
        
        return $this->redirect(['action' => 'manageChildren', $scheduleId]);
    }

    /**
     * Remove child from schedule - Deletes assignment
     *
     * @return \Cake\Http\Response|null Redirects back
     */
    public function removeChild()
    {
        $this->request->allowMethod(['post']);
        
        $data = $this->request->getData();
        $scheduleId = $data['schedule_id'];
        $childId = $data['child_id'];
        
        // Find and delete assignment
        $assignment = $this->fetchTable('Assignments')->find()
            ->innerJoinWith('ScheduleDays')
            ->where([
                'ScheduleDays.schedule_id' => $scheduleId,
                'Assignments.child_id' => $childId,
            ])
            ->first();
        
        if ($assignment && $this->fetchTable('Assignments')->delete($assignment)) {
            $this->Flash->success(__('Child removed from schedule.'));
        } else {
            $this->Flash->error(__('Could not remove child from schedule.'));
        }
        
        return $this->redirect(['action' => 'manageChildren', $scheduleId]);
    }

    /**
     * Generate Report - Create Ausfallplan
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void
     */
    public function generateReport($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: ['Organizations']);
        
        // Get assigned children count for default days_count suggestion
        $assignedChildrenCount = $this->fetchTable('Assignments')->find()
            ->select(['child_id' => 'DISTINCT Assignments.child_id'])
            ->innerJoinWith('ScheduleDays')
            ->where(['ScheduleDays.schedule_id' => $schedule->id])
            ->count();
        
        // Use days_count from schedule or default to assigned children count
        $daysCount = $schedule->days_count ?? $assignedChildrenCount;
        
        // Generate report using ReportService
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        // Render report view
        $this->viewBuilder()->setLayout('ajax'); // No layout for clean print
        $this->set(compact('reportData'));
    }
}
