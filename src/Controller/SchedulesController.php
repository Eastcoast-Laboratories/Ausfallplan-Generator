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

        // Count children per schedule (from waitlist_entries)
        $waitlistTable = $this->fetchTable('WaitlistEntries');
        
        $childrenCounts = [];
        foreach ($schedules as $schedule) {
            // Count children in waitlist for this schedule
            $count = $waitlistTable->find()
                ->where(['schedule_id' => $schedule->id])
                ->count();
                
            $childrenCounts[$schedule->id] = $count;
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
            $this->request->getSession()->write('activeScheduleId', $scheduleId);
            
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(['success' => true, 'message' => __('Active schedule set')]));
        }
        
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['success' => false, 'message' => __('Invalid schedule ID')]));
    }

    /**
     * View method
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void
     */
    public function view($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: ['Organizations']);
        
        // Permission check: User must be member of schedule's organization
        if (!$this->hasOrgRole($schedule->organization_id)) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }

        $this->set(compact('schedule'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add()
    {
        $schedule = $this->Schedules->newEmptyEntity();
        $user = $this->Authentication->getIdentity();
        
        // Get user's organizations for dropdown
        $organizations = collection($this->getUserOrganizations())
            ->combine('id', 'name')
            ->toArray();
        
        if ($this->request->is('post')) {
            $schedule = $this->Schedules->patchEntity($schedule, $this->request->getData());
            
            // Set user_id to current user
            $schedule->user_id = $user->id;
            
            // Validate organization membership
            if (!$this->hasOrgRole($schedule->organization_id)) {
                $this->Flash->error(__('You do not have permission to create schedules for this organization.'));
                return $this->redirect(['action' => 'add']);
            }
            
            // Ensure organization_id is set
            if (!$schedule->organization_id) {
                $this->Flash->error(__('Please select an organization.'));
                return $this->redirect(['action' => 'add']);
            }
            
            if ($this->Schedules->save($schedule)) {
                // Set this as the active schedule in session
                $this->request->getSession()->write('activeScheduleId', $schedule->id);
                
                $this->Flash->success(__('Schedule created successfully.'));
                return $this->redirect(['action' => 'view', $schedule->id]);
            }
            $this->Flash->error(__('Could not save schedule. Please try again.'));
        }
        
        $this->set(compact('schedule', 'organizations'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     */
    public function edit($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: []);
        $user = $this->Authentication->getIdentity();
        
        // Permission check: User must be member of schedule's organization
        if (!$this->hasOrgRole($schedule->organization_id)) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Get user's organizations for dropdown
        $organizations = collection($this->getUserOrganizations())
            ->combine('id', 'name')
            ->toArray();
        
        if ($this->request->is(['patch', 'post', 'put'])) {
            $schedule = $this->Schedules->patchEntity($schedule, $this->request->getData());
            
            // Ensure user_id is set
            if (!$schedule->user_id) {
                $schedule->user_id = $user->id;
            }
            
            if ($this->Schedules->save($schedule)) {
                // Set this as the active schedule in session
                $this->request->getSession()->write('activeScheduleId', $schedule->id);
                
                $this->Flash->success(__('Schedule updated successfully.'));
                return $this->redirect(['action' => 'view', $schedule->id]);
            }
            $this->Flash->error(__('Could not save schedule. Please try again.'));
        }
        
        $this->set(compact('schedule', 'organizations'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $schedule = $this->Schedules->get($id);
        
        // Permission check: User must be member of schedule's organization
        if (!$this->hasOrgRole($schedule->organization_id)) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }
        
        if ($this->Schedules->delete($schedule)) {
            $this->Flash->success(__('Schedule deleted successfully.'));
        } else {
            $this->Flash->error(__('Could not delete schedule. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Manage children - Shows assigned children and allows adding/removing
     *
     * @param string|null $id Schedule id
     * @return \Cake\Http\Response|null|void
     */
    public function manageChildren($id = null)
    {
        $schedule = $this->Schedules->get($id);
        
        // Get all children in waitlist with priority (sorted)
        $waitlistTable = $this->fetchTable("WaitlistEntries");
        $assignedChildrenData = $waitlistTable->find()
            ->contain(['Children'])
            ->where(['WaitlistEntries.schedule_id' => $schedule->id])
            ->orderBy(['WaitlistEntries.priority' => 'ASC'])
            ->all();
        
        $assignedChildren = [];
        foreach ($assignedChildrenData as $entry) {
            $assignedChildren[] = $entry->child;
        }
        
        // Get all available children from same organization
        $childrenTable = $this->fetchTable("Children");
        $allChildren = $childrenTable->find()
            ->where(["Children.organization_id" => $schedule->organization_id])
            ->orderBy(["Children.display_name" => "ASC"])
            ->all();
        
        $this->set(compact("schedule", "assignedChildren", "allChildren"));
    }

    /**
     * Assign child to schedule - Adds to waitlist_entries
     *
     * @return \Cake\Http\Response|null
     */
    public function assignChild()
    {
        $this->request->allowMethod(["post"]);
        
        $scheduleId = $this->request->getData("schedule_id");
        $childId = $this->request->getData("child_id");
        
        if (!$scheduleId || !$childId) {
            $this->Flash->error(__('Invalid data.'));
            return $this->redirect(['action' => 'index']);
        }
        
        $waitlistTable = $this->fetchTable("WaitlistEntries");
        
        // Check if already assigned
        $existing = $waitlistTable->find()
            ->where(['schedule_id' => $scheduleId, 'child_id' => $childId])
            ->first();
        
        if ($existing) {
            $this->Flash->error(__('Child is already assigned to this schedule.'));
            return $this->redirect(["action" => "manageChildren", $scheduleId]);
        }
        
        // Get max priority for this schedule
        $maxPriority = $waitlistTable->find()
            ->where(['schedule_id' => $scheduleId])
            ->select(['max_priority' => 'MAX(priority)'])
            ->first();
        
        $nextPriority = ($maxPriority && $maxPriority->max_priority) ? $maxPriority->max_priority + 1 : 1;
        
        // Create waitlist entry
        $entry = $waitlistTable->newEntity([
            "schedule_id" => $scheduleId,
            "child_id" => $childId,
            "priority" => $nextPriority,
        ]);
        
        if ($waitlistTable->save($entry)) {
            $this->Flash->success(__('Child added successfully.'));
        } else {
            $this->Flash->error(__('Could not add child. Please try again.'));
        }
        
        return $this->redirect(["action" => "manageChildren", $scheduleId]);
    }

    /**
     * Remove child from schedule - Deletes from waitlist_entries
     *
     * @return \Cake\Http\Response|null Redirects back
     */
    public function removeChild()
    {
        $this->request->allowMethod(['post']);
        
        $scheduleId = $this->request->getData('schedule_id');
        $childId = $this->request->getData('child_id');
        
        if (!$scheduleId || !$childId) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Delete from waitlist_entries
        $waitlistTable = $this->fetchTable('WaitlistEntries');
        $deleted = $waitlistTable->deleteAll([
            'schedule_id' => $scheduleId,
            'child_id' => $childId
        ]);
        
        if ($deleted > 0) {
            $this->Flash->success(__('Kind erfolgreich entfernt.'));
        } else {
            $this->Flash->error(__('Kind konnte nicht entfernt werden.'));
        }
        
        return $this->redirect(['action' => 'manageChildren', $scheduleId]);
    }

    /**
     * Generate report view
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void
     */
    public function generateReport($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: ['Organizations']);
        
        // Get children count from waitlist for default days_count suggestion
        $waitlistTable = $this->fetchTable('WaitlistEntries');
        $assignedChildrenCount = $waitlistTable->find()
            ->where(['WaitlistEntries.schedule_id' => $schedule->id])
            ->count();
        
        // Use days_count from schedule or default to assigned children count
        $daysCount = $schedule->days_count ?? $assignedChildrenCount;
        
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        // Set up view
        $this->viewBuilder()->setLayout('print');
        $this->set('schedule', $reportData['schedule']);
        $this->set('days', $reportData['days']);
        $this->set('daysCount', $reportData['daysCount']);
        $this->set('childStats', $reportData['childStats']);
    }

    /**
     * Export CSV
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null
     */
    public function exportCsv($id = null)
    {
        $schedule = $this->Schedules->get($id, [
            'contain' => ['Organizations'],
        ]);
        
        // Permission check: User must be member of schedule's organization
        if (!$this->hasOrgRole($schedule->organization_id)) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Get children count from waitlist
        $waitlistTable = $this->fetchTable('WaitlistEntries');
        $assignedChildrenCount = $waitlistTable->find()
            ->where(['WaitlistEntries.schedule_id' => $schedule->id])
            ->count();
        
        $daysCount = $schedule->days_count ?? $assignedChildrenCount;
        
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        // Build CSV content
        $csv = [];
        
        // Header
        $csv[] = ['Ausfallplan', $schedule->title];
        $csv[] = ['Organisation', $schedule->organization->name];
        $csv[] = [''];
        
        // Days header
        $header = ['Tag'];
        for ($i = 1; $i <= $daysCount; $i++) {
            $header[] = $reportData['days'][$i-1]['name'] ?? "Tag $i";
        }
        $csv[] = $header;
        
        // Child rows
        $allChildren = [];
        foreach ($reportData['days'] as $day) {
            foreach ($day['children'] as $child) {
                if (!isset($allChildren[$child->id])) {
                    $allChildren[$child->id] = [
                        'name' => $child->display_name,
                        'days' => []
                    ];
                }
                $allChildren[$child->id]['days'][] = $day['name'];
            }
        }
        
        foreach ($allChildren as $childData) {
            $row = [$childData['name']];
            for ($i = 1; $i <= $daysCount; $i++) {
                $dayName = $reportData['days'][$i-1]['name'] ?? "Tag $i";
                $row[] = in_array($dayName, $childData['days']) ? 'X' : '';
            }
            $csv[] = $row;
        }
        
        // Statistics
        $csv[] = [''];
        $csv[] = ['Statistik'];
        foreach ($reportData['childStats'] as $childId => $stats) {
            $childName = '';
            foreach ($allChildren as $id => $data) {
                if ($id == $childId) {
                    $childName = $data['name'];
                    break;
                }
            }
            $csv[] = [$childName, 'Zuweisungen: ' . $stats['assignments']];
        }
        
        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row, ';');
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        // Send as download
        $this->response = $this->response
            ->withType('text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="ausfallplan_' . $schedule->id . '.csv"')
            ->withStringBody($csvContent);
        
        return $this->response;
    }

    /**
     * Reorder children via AJAX - Updates waitlist_entries.priority
     *
     * @return \Cake\Http\Response
     */
    public function reorderChildren()
    {
        $this->request->allowMethod(["post"]);
        $data = $this->request->getData();
        $scheduleId = $data["schedule_id"] ?? null;
        $order = $data["order"] ?? [];
        
        if (!$scheduleId || !is_array($order)) {
            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode(["success" => false, "error" => "Invalid data"]));
        }
        
        // Update priorities in waitlist_entries
        $waitlistTable = $this->fetchTable("WaitlistEntries");
        $success = true;
        
        foreach ($order as $index => $childId) {
            $priority = $index + 1;
            
            // Update priority for this child in this schedule
            $entry = $waitlistTable->find()
                ->where([
                    "schedule_id" => $scheduleId,
                    "child_id" => $childId
                ])
                ->first();
            
            if ($entry) {
                $entry->priority = $priority;
                if (!$waitlistTable->save($entry)) {
                    $success = false;
                }
            }
        }
        
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(["success" => $success]));
    }
}
