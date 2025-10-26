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
        
        // System admin sees all schedules with user info
        if ($user && $user->is_system_admin) {
            $schedules = $this->Schedules->find()
                ->contain(['Organizations', 'Users'])
                ->orderBy(['Schedules.created' => 'DESC'])
                ->all();
        } else {
            // Regular users see schedules from their organization(s)
            $userOrgs = $this->getUserOrganizations();
            $orgIds = array_map(fn($org) => $org->id, $userOrgs);
            
            if (!empty($orgIds)) {
                $schedules = $this->Schedules->find()
                    ->where(['Schedules.organization_id IN' => $orgIds])
                    ->contain(['Organizations', 'Users'])
                    ->orderBy(['Schedules.created' => 'DESC'])
                    ->all();
            } else {
                $schedules = [];
            }
        }

        // Get active schedule from session for highlighting
        $activeScheduleId = $this->request->getSession()->read('activeScheduleId');

        // Count children per schedule
        $assignmentsTable = $this->fetchTable('Assignments');
        $scheduleDaysTable = $this->fetchTable('ScheduleDays');
        
        $childrenCounts = [];
        foreach ($schedules as $schedule) {
            // Get schedule_day_ids for this schedule
            $scheduleDayIds = $scheduleDaysTable->find()
                ->where(['schedule_id' => $schedule->id])
                ->select(['id'])
                ->all()
                ->extract('id')
                ->toArray();
            
            if (!empty($scheduleDayIds)) {
                // Count unique children assigned to these schedule days
                $count = $assignmentsTable->find()
                    ->where(['schedule_day_id IN' => $scheduleDayIds])
                    ->select(['child_id'])
                    ->distinct(['child_id'])
                    ->count();
                
                $childrenCounts[$schedule->id] = $count;
            } else {
                $childrenCounts[$schedule->id] = 0;
            }
        }

        $this->set(compact('schedules', 'user', 'activeScheduleId', 'childrenCounts'));
    }

    /**
     * Set active schedule in session via AJAX
     *
     * @return \Cake\Http\Response JSON response
     */
    public function setActive()
    {
        $this->request->allowMethod(['post']);
        
        $scheduleId = $this->request->getData('schedule_id');
        
        if ($scheduleId) {
            $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
            
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => __('Active schedule set.')
                ]));
        } else {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'error' => __('Invalid schedule ID.')
                ]));
        }
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
        
        // Permission check: User must be member of schedule's organization
        if (!$this->hasOrgRole($schedule->organization_id)) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }

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
        $user = $this->Authentication->getIdentity();
        
        // Get available organizations for this user
        $orgEntities = $this->getUserOrganizations();
        $organizations = collection($orgEntities)->combine('id', 'name')->toArray();
        $canSelectOrganization = $user->is_system_admin || count($organizations) > 1;
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Determine organization_id
            if (!empty($data['organization_id']) && $canSelectOrganization) {
                // User selected an organization (system admin or multiple orgs)
                $selectedOrgId = $data['organization_id'];
                
                // Verify user has access to this organization
                if (!$user->is_system_admin && !isset($organizations[$selectedOrgId])) {
                    $this->Flash->error(__('Zugriff auf diese Organisation verweigert.'));
                    return $this->redirect(['action' => 'add']);
                }
                
                $data['organization_id'] = $selectedOrgId;
            } else {
                // Use primary organization (single org user)
                $primaryOrg = $this->getPrimaryOrganization();
                if (!$primaryOrg) {
                    $this->Flash->error(__('Sie müssen einer Organisation angehören, um Dienstpläne zu erstellen.'));
                    return $this->redirect(['action' => 'index']);
                }
                $data['organization_id'] = $primaryOrg->id;
            }
            
            $data['user_id'] = $user->id;
            
            // Set default state to 'draft'
            if (empty($data['state'])) {
                $data['state'] = 'draft';
            }
            
            $schedule = $this->Schedules->patchEntity($schedule, $data);
            
            if ($this->Schedules->save($schedule)) {
                // Generate schedule days based on dates or days_count
                $this->generateScheduleDays($schedule);
                
                // Set this as the active schedule in session
                $this->request->getSession()->write('activeScheduleId', $schedule->id);
                
                $this->Flash->success(__('Der Dienstplan und die Tage wurden erstellt.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The schedule could not be saved. Please try again.'));
        }
        
        $this->set(compact('schedule', 'organizations', 'canSelectOrganization'));
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
        
        // Permission check - requires editor role in schedule's organization
        if (!$this->hasOrgRole($schedule->organization_id, 'editor')) {
            $this->Flash->error(__('Sie haben keine Berechtigung Dienstpläne zu bearbeiten.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $schedule = $this->Schedules->patchEntity($schedule, $this->request->getData());
            
            if ($this->Schedules->save($schedule)) {
                // Regenerate schedule days if dates changed
                $this->generateScheduleDays($schedule);
                
                // Set this as the active schedule in session
                $this->request->getSession()->write('activeScheduleId', $schedule->id);
                
                $this->Flash->success(__('Der Dienstplan wurde aktualisiert.'));
                return $this->redirect(['action' => 'view', $schedule->id]);
            }
            
            $this->Flash->error(__('Der Dienstplan konnte nicht gespeichert werden. Bitte versuchen Sie es erneut.'));
        }
        
        $this->set(compact('schedule', 'organizations', 'canSelectOrganization'));
    }

    /**
     * Delete method - Remove a schedule
     *
// ...
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $schedule = $this->Schedules->get($id);
        
        // Permission check - requires editor role in schedule's organization
        if (!$this->hasOrgRole($schedule->organization_id, 'editor')) {
            $this->Flash->error(__('Sie haben keine Berechtigung Dienstpläne zu löschen.'));
            return $this->redirect(['action' => 'index']);
        }

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
        
        // Get available children (not yet assigned) from schedule's organization
        $availableChildrenQuery = $this->fetchTable("Children")->find()
            ->where([
                "Children.organization_id" => $schedule->organization_id,
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
        
        $queryParams = $this->request->getQueryParams();
        $scheduleId = $queryParams["schedule_id"] ?? null;
        $childId = $queryParams["child_id"] ?? null;
        
        if (!$scheduleId || !$childId) {
            $this->Flash->error(__("Ungültige Parameter."));
            return $this->redirect(["action" => "index"]);
        }
        
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
        
        // Try both query params and post data
        $queryParams = $this->request->getQueryParams();
        $postData = $this->request->getData();
        
        $scheduleId = $queryParams['schedule_id'] ?? $postData['schedule_id'] ?? null;
        $childId = $queryParams['child_id'] ?? $postData['child_id'] ?? null;
        
        if (!$scheduleId || !$childId) {
            $this->Flash->error(__('Ungültige Parameter.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Find and delete ALL assignments for this child in this schedule
        $assignmentsTable = $this->fetchTable('Assignments');
        $scheduleDaysTable = $this->fetchTable('ScheduleDays');
        
        // Get all schedule_day_ids for this schedule
        $scheduleDayIds = $scheduleDaysTable->find()
            ->where(['schedule_id' => $scheduleId])
            ->select(['id'])
            ->all()
            ->extract('id')
            ->toArray();
        
        if (!empty($scheduleDayIds)) {
            // Delete all assignments for this child in these days
            $deleted = $assignmentsTable->deleteAll([
                'schedule_day_id IN' => $scheduleDayIds,
                'child_id' => $childId
            ]);
            
            if ($deleted > 0) {
                $this->Flash->success(__('Child removed from schedule.'));
            } else {
                $this->Flash->error(__('Could not remove child from schedule.'));
            }
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
     * Export schedule as CSV
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null
     */
    public function exportCsv($id = null)
    {
        $schedule = $this->Schedules->get($id, [
            'contain' => ['Organizations'],
        ]);
        
        $this->Authorization->authorize($schedule);
        
        // Get same data as report
        $assignedChildrenCount = $this->Schedules->ScheduleChildren
            ->find()
            ->where(['ScheduleChildren.schedule_id' => $schedule->id])
            ->count();
        
        $daysCount = $schedule->days_count ?? $assignedChildrenCount;
        
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        // Build CSV content
        $csv = [];
        
        // Header
        $csv[] = ['Ausfallplan', $schedule->name];
        $csv[] = ['Datum', $schedule->start_date->format('d.m.Y')];
        $csv[] = ['Organisation', $schedule->organization->name];
        $csv[] = [];
        
        // Waitlist
        $csv[] = ['Nachrückliste'];
        $csv[] = ['Name', 'Priorität', 'Integrativ'];
        foreach ($reportData['waitlist'] as $entry) {
            $csv[] = [
                $entry->child->name,
                $entry->priority,
                $entry->child->is_integrative ? 'Ja' : 'Nein'
            ];
        }
        $csv[] = [];
        
        // Always at end
        if (!empty($reportData['alwaysAtEnd'])) {
            $csv[] = ['Immer am Ende'];
            $csv[] = ['Name', 'Gewichtung', 'Integrativ'];
            foreach ($reportData['alwaysAtEnd'] as $childData) {
                $csv[] = [
                    $childData['child']->name,
                    $childData['weight'],
                    $childData['child']->is_integrative ? 'Ja' : 'Nein'
                ];
            }
            $csv[] = [];
        }
        
        // Days
        $csv[] = ['Tage'];
        $dayHeaders = ['Tag'];
        foreach ($reportData['days'] as $day) {
            $dayHeaders[] = 'Tag ' . $day['day_number'];
        }
        $csv[] = $dayHeaders;
        
        // Get max children per day
        $maxChildren = 0;
        foreach ($reportData['days'] as $day) {
            $maxChildren = max($maxChildren, count($day['children']));
        }
        
        // Add children rows
        for ($i = 0; $i < $maxChildren; $i++) {
            $row = ['Kind ' . ($i + 1)];
            foreach ($reportData['days'] as $day) {
                $row[] = isset($day['children'][$i]) ? $day['children'][$i]->name : '';
            }
            $csv[] = $row;
        }
        
        // Convert to CSV string
        $output = fopen('php://temp', 'w');
        foreach ($csv as $row) {
            fputcsv($output, $row, ';'); // Use semicolon for German Excel
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        // Return as download
        $filename = 'ausfallplan_' . $schedule->name . '_' . date('Y-m-d') . '.csv';
        
        $this->response = $this->response->withStringBody($csvContent);
        $this->response = $this->response->withType('text/csv');
        $this->response = $this->response->withDownload($filename);
        
        return $this->response;
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

    /**
     * Generate schedule days for a schedule based on start/end dates or days_count
     *
     * @param \App\Model\Entity\Schedule $schedule
     * @return void
     */
    private function generateScheduleDays($schedule): void
    {
        $scheduleDaysTable = $this->fetchTable('ScheduleDays');
        
        // Delete existing schedule days for this schedule
        $scheduleDaysTable->deleteAll(['schedule_id' => $schedule->id]);
        
        // Calculate number of days
        $daysCount = 0;
        if ($schedule->ends_on) {
            // Calculate days between start and end date
            $start = new \DateTime($schedule->starts_on->format('Y-m-d'));
            $end = new \DateTime($schedule->ends_on->format('Y-m-d'));
            $daysCount = $start->diff($end)->days + 1; // +1 to include both start and end
        } elseif ($schedule->days_count && $schedule->days_count > 0) {
            // Use specified days_count
            $daysCount = $schedule->days_count;
        } else {
            // Default: 1 day
            $daysCount = 1;
        }
        
        // Create schedule days
        for ($i = 0; $i < $daysCount; $i++) {
            $date = (new \DateTime($schedule->starts_on->format('Y-m-d')))->modify("+{$i} days");
            
            $scheduleDay = $scheduleDaysTable->newEntity([
                'schedule_id' => $schedule->id,
                'title' => 'Tag ' . ($i + 1) . ' (' . $date->format('d.m.Y') . ')',
                'position' => $i + 1,
                'capacity' => $schedule->capacity_per_day ?? 9,
            ]);
            
            $scheduleDaysTable->save($scheduleDay);
        }
    }
}
