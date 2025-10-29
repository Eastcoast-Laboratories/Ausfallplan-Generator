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

        // Count children per schedule (from children table)
        $childrenTable = $this->fetchTable('Children');
        
        $childrenCounts = [];
        foreach ($schedules as $schedule) {
            // Count children on waitlist for this schedule
            $count = $childrenTable->find()
                ->where([
                    'schedule_id' => $schedule->id,
                    'waitlist_order IS NOT' => null
                ])
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
        
        // Can select organization if system admin or has multiple orgs
        $canSelectOrganization = $user->is_system_admin || count($organizations) > 1;
        
        if ($this->request->is('post')) {
            $schedule = $this->Schedules->patchEntity($schedule, $this->request->getData());
            
            // Set user_id to current user
            $schedule->user_id = $user->id;
            
            // Ensure organization_id is set FIRST (before validation)
            if (!$schedule->organization_id) {
                $this->Flash->error(__('Please select an organization.'));
                $this->set(compact('schedule', 'organizations', 'canSelectOrganization'));
                return;
            }
            
            // Validate organization membership
            if (!$this->hasOrgRole($schedule->organization_id)) {
                $this->Flash->error(__('You do not have permission to create schedules for this organization.'));
                $this->set(compact('schedule', 'organizations', 'canSelectOrganization'));
                return;
            }
            
            if ($this->Schedules->save($schedule)) {
                // Set this as the active schedule in session
                $this->request->getSession()->write('activeScheduleId', $schedule->id);
                
                $this->Flash->success(__('Schedule created successfully.'));
                return $this->redirect(['action' => 'view', $schedule->id]);
            }
            $this->Flash->error(__('Could not save schedule. Please try again.'));
        }
        
        $this->set(compact('schedule', 'organizations', 'canSelectOrganization'));
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
     * Manage children - Sort children by organization_order
     *
     * @param string|null $id Schedule id
     * @return \Cake\Http\Response|null|void
     */
    public function manageChildren($id = null)
    {
        $schedule = $this->Schedules->get($id, [
            'contain' => ['Organizations']
        ]);

        // Check permission
        $this->checkScheduleAccess($schedule, 'edit');

        // Handle AJAX reorder request
        if ($this->request->is('post') && $this->request->is('ajax')) {
            $this->request->allowMethod(['post']);
            $childrenIds = $this->request->getData('children');
            
            if ($childrenIds) {
                $childrenTable = $this->fetchTable('Children');
                $success = true;
                
                // Update organization_order for each child
                foreach ($childrenIds as $order => $childId) {
                    $child = $childrenTable->get($childId);
                    $child->organization_order = $order + 1;
                    if (!$childrenTable->save($child)) {
                        $success = false;
                        break;
                    }
                }
                
                $this->set([
                    'success' => $success,
                    '_serialize' => ['success']
                ]);
                $this->viewBuilder()->setOption('serialize', ['success']);
                return $this->response->withType('application/json')
                    ->withStringBody(json_encode(['success' => $success]));
            }
        }

        // Get children for this organization, sorted by organization_order
        $childrenTable = $this->fetchTable('Children');
        $children = $childrenTable->find()
            ->where(['Children.organization_id' => $schedule->organization_id])
            ->contain(['SiblingGroups'])
            ->orderBy(['Children.organization_order' => 'ASC', 'Children.id' => 'ASC'])
            ->all();

        $this->set(compact('schedule', 'children'));
    }

    /**
     * Assign child to schedule - Deprecated, redirect to Waitlist
     *
     * @return \Cake\Http\Response
     */
    public function assignChild()
    {
        $this->Flash->info(__('Please use the Waitlist page to manage children.'));
        return $this->redirect(['controller' => 'Waitlist', 'action' => 'index']);
    }

    /**
     * Remove child from schedule - Deprecated, redirect to Waitlist
     *
     * @return \Cake\Http\Response
     */
    public function removeChild()
    {
        $this->Flash->info(__('Please use the Waitlist page to manage children.'));
        return $this->redirect(['controller' => 'Waitlist', 'action' => 'index']);
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
        $childrenTable = $this->fetchTable('Children');
        $assignedChildrenCount = $childrenTable->find()
            ->where([
                'schedule_id' => $schedule->id,
                'waitlist_order IS NOT' => null
            ])
            ->count();
        
        // Use days_count from schedule or default to assigned children count
        $daysCount = $schedule->days_count ?? $assignedChildrenCount;
        
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        // Punkt 5: Generate 2D grid
        $gridService = new \App\Service\ReportGridService();
        $gridData = $gridService->generateGrid($reportData);
        
        // Set up view
        $this->viewBuilder()->setLayout('print');
        $this->set('schedule', $reportData['schedule']);
        $this->set('days', $reportData['days']);
        $this->set('daysCount', $reportData['daysCount']);
        $this->set('childStats', $reportData['childStats']);
        $this->set('waitlist', $reportData['waitlist'] ?? []);
        $this->set('alwaysAtEnd', $reportData['alwaysAtEnd'] ?? []);
        $this->set('grid', $gridData['grid'] ?? []);
        $this->set('gridMetadata', $gridData['metadata'] ?? []);
    }

    /**
     * Generate report as grid table (Punkt 5)
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void
     */
    public function generateReportGrid($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: ['Organizations']);
        
        // Permission check
        if (!$this->hasOrgRole($schedule->organization_id)) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Get children count from waitlist for default days_count suggestion
        $childrenTable = $this->fetchTable('Children');
        $assignedChildrenCount = $childrenTable->find()
            ->where([
                'schedule_id' => $schedule->id,
                'waitlist_order IS NOT' => null
            ])
            ->count();
        
        // Use days_count from schedule or default to assigned children count
        $daysCount = $schedule->days_count ?? $assignedChildrenCount;
        
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        // Generate 2D grid
        $gridService = new \App\Service\ReportGridService();
        $gridData = $gridService->generateGrid($reportData);
        
        // Set up view
        $this->viewBuilder()->setLayout('print');
        $this->set('schedule', $reportData['schedule']);
        $this->set('grid', $gridData['grid'] ?? []);
        $this->set('gridMetadata', $gridData['metadata'] ?? []);
        $this->render('generate_report_grid');
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
        $childrenTable = $this->fetchTable('Children');
        $assignedChildrenCount = $childrenTable->find()
            ->where([
                'schedule_id' => $schedule->id,
                'waitlist_order IS NOT' => null
            ])
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
     * Reorder children via AJAX - Deprecated, redirect to Waitlist
     *
     * @return \Cake\Http\Response
     */
    public function reorderChildren()
    {
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                "success" => false,
                "error" => "Please use the Waitlist controller to reorder children"
            ]));
    }
}
