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
        // Get current user
        $user = $this->Authentication->getIdentity();
        
        // Admin sees all schedules with user info, normal users only their own
        if ($user->role === 'admin') {
            $schedules = $this->Schedules->find()
                ->contain(['Organizations', 'Users'])
                ->orderBy(['Schedules.created' => 'DESC'])
                ->all();
        } else {
            $schedules = $this->Schedules->find()
                ->where(['Schedules.user_id' => $user->id])
                ->contain(['Organizations'])
                ->orderBy(['Schedules.created' => 'DESC'])
                ->all();
        }

        $this->set(compact('schedules', 'user'));
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
            
            // Set organization and user from current user
            $user = $this->Authentication->getIdentity();
            $data['organization_id'] = $user->organization_id;
            $data['user_id'] = $user->id;
            
            // Set default state to 'draft'
            if (empty($data['state'])) {
                $data['state'] = 'draft';
            }
            
            $schedule = $this->Schedules->patchEntity($schedule, $data);
            
            if ($this->Schedules->save($schedule)) {
                // Set this as the active schedule in session
                $this->request->getSession()->write('activeScheduleId', $schedule->id);
                
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
                // Set this as the active schedule in session
                $this->request->getSession()->write('activeScheduleId', $schedule->id);
                
                $this->Flash->success(__('The schedule has been updated.'));
                return $this->redirect(['action' => 'view', $schedule->id]);
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
        
        // Get all children in this schedule (via assignments) with sort_order
        $assignedChildrenIds = $this->fetchTable("Assignments")->find()
            ->select(["child_id" => "DISTINCT Assignments.child_id", "sort_order" => "MIN(Assignments.sort_order)"])
            ->innerJoinWith("ScheduleDays")
            ->where(["ScheduleDays.schedule_id" => $schedule->id])
            ->group(["Assignments.child_id"])
            ->orderBy(["sort_order" => "ASC"])
            ->all()
            ->extract("child_id")
            ->toArray();
        
        // Get assigned children details with sibling_group_id
        $assignedChildren = [];
        if (!empty($assignedChildrenIds)) {
            $childrenTable = $this->fetchTable("Children");
            $assignedChildren = $childrenTable->find()
                ->where(["Children.id IN" => $assignedChildrenIds])
                ->orderBy([
                    // Maintain the sort order from assignments
                    "FIELD(Children.id, " . implode(",", $assignedChildrenIds) . ")" => "ASC"
                ])
                ->all();
        }
        
        // Get available children (not yet assigned)
        $user = $this->Authentication->getIdentity();
        $availableChildrenQuery = $this->fetchTable("Children")->find()
            ->where([
                "Children.organization_id" => $user->organization_id,
                "Children.is_active" => true,
            ])
            ->orderBy(["Children.name" => "ASC"]);
        
        if (!empty($assignedChildrenIds)) {
            $availableChildrenQuery->where([
                "Children.id NOT IN" => $assignedChildrenIds
            ]);
        }
        
        $availableChildren = $availableChildrenQuery->all();
        
        $this->set(compact("schedule", "assignedChildren", "availableChildren"));
    }

    /**
     * Assign child to schedule
     *
     * @return \Cake\Http\Response|null
     */
    public function assignChild()
    {
        $this->request->allowMethod(["post"]);
        
        $scheduleId = $this->request->getQuery("schedule_id");
        $childId = $this->request->getQuery("child_id");
        
        // Get schedule days for this schedule
        $scheduleDaysTable = $this->fetchTable("ScheduleDays");
        $scheduleDays = $scheduleDaysTable->find()
            ->where(["schedule_id" => $scheduleId])
            ->all();
        
        if ($scheduleDays->count() === 0) {
            $this->Flash->error(__("Dieser Dienstplan hat noch keine Tage. Bitte fügen Sie zuerst Tage zum Dienstplan hinzu, bevor Sie Kinder zuweisen können."));
            return $this->redirect(["action" => "view", $scheduleId]);
        }
        
        // Get max sort_order for this schedule
        $assignmentsTable = $this->fetchTable("Assignments");
        $maxSortOrder = $assignmentsTable->find()
            ->select(["max_sort" => "MAX(sort_order)"])
            ->innerJoinWith("ScheduleDays")
            ->where(["ScheduleDays.schedule_id" => $scheduleId])
            ->first();
        
        $nextSortOrder = ($maxSortOrder && $maxSortOrder->max_sort) ? $maxSortOrder->max_sort + 1 : 1;
        
        // Assign child to all schedule days
        $success = true;
        foreach ($scheduleDays as $day) {
            $assignment = $assignmentsTable->newEntity([
                "schedule_day_id" => $day->id,
                "child_id" => $childId,
                "weight" => 1,
                "source" => "manual",
                "sort_order" => $nextSortOrder,
            ]);
            
            if (!$assignmentsTable->save($assignment)) {
                $success = false;
            }
        }
        
        if ($success) {
            $this->Flash->success(__("Child assigned to schedule."));
        } else {
            $this->Flash->error(__("Could not assign child to schedule."));
        }
        
        return $this->redirect(["action" => "manageChildren", $scheduleId]);
    }
    /**
     * Remove child from schedule - Deletes assignment
     *
     * @return \Cake\Http\Response|null Redirects back
     */
    public function removeChild()
    {
        $this->request->allowMethod(['post']);
        
        $scheduleId = $this->request->getQuery('schedule_id');
        $childId = $this->request->getQuery('child_id');
        
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
        
        // Extract data for template (use reportSchedule to avoid overwriting $schedule)
        $reportSchedule = $reportData['schedule'];
        $days = $reportData['days'];
        $waitlist = $reportData['waitlist'];
        $alwaysAtEnd = $reportData['alwaysAtEnd'];
        $reportDaysCount = $reportData['daysCount'];
        $childStats = $reportData['childStats'];
        
        // Render report view
        $this->viewBuilder()->setLayout('ajax'); // No layout for clean print
        $this->set(compact('reportSchedule', 'days', 'waitlist', 'alwaysAtEnd', 'reportDaysCount', 'childStats'));
        $this->set('schedule', $reportSchedule); // Also set schedule for backward compatibility
        $this->set('daysCount', $reportDaysCount);
    }

    /**
     * Reorder assigned children via AJAX
     *
     * @return \Cake\Http\Response
     */
    public function reorderChildren()
    {
        $this->request->allowMethod(["post"]);
        $data = $this->request->getData();
        
        $scheduleId = $data["schedule_id"] ?? null;
        $order = $data["order"] ?? [];
        
        if (!$scheduleId || empty($order)) {
            return $this->response
                ->withType("application/json")
                ->withStringBody(json_encode(["success" => false, "error" => "Invalid data"]));
        }
        
        // Get schedule_days for this schedule
        $scheduleDaysTable = $this->fetchTable("ScheduleDays");
        $scheduleDays = $scheduleDaysTable->find()
            ->where(["schedule_id" => $scheduleId])
            ->all()
            ->extract("id")
            ->toArray();
        
        if (empty($scheduleDays)) {
            return $this->response
                ->withType("application/json")
                ->withStringBody(json_encode(["success" => false, "error" => "No schedule days found"]));
        }
        
        // Update sort_order for assignments
        $assignmentsTable = $this->fetchTable("Assignments");
        $success = true;
        
        foreach ($order as $index => $childId) {
            $sortOrder = $index + 1;
            
            // Update all assignments for this child in this schedule
            $assignments = $assignmentsTable->find()
                ->where([
                    "child_id" => $childId,
                    "schedule_day_id IN" => $scheduleDays
                ])
                ->all();
            
            foreach ($assignments as $assignment) {
                $assignment->sort_order = $sortOrder;
                if (!$assignmentsTable->save($assignment)) {
                    $success = false;
                }
            }
        }
        
        return $this->response
            ->withType("application/json")
            ->withStringBody(json_encode(["success" => $success]));
    }
}
