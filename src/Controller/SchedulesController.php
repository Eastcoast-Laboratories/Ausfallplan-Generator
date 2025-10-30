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
        $missingSiblingsPerSchedule = [];
        
        foreach ($schedules as $schedule) {
            // Count children with organization_order for this schedule
            $count = $childrenTable->find()
                ->where([
                    'schedule_id' => $schedule->id,
                    'organization_order IS NOT' => null
                ])
                ->count();
                
            $childrenCounts[$schedule->id] = $count;
            
            // Find missing siblings for this schedule
            $childrenInSchedule = $childrenTable->find()
                ->where([
                    'schedule_id' => $schedule->id,
                    'organization_order IS NOT' => null
                ])
                ->all();
            
            $missingSiblings = $this->findMissingSiblings($childrenInSchedule, $schedule->id);
            if (!empty($missingSiblings)) {
                $missingSiblingsPerSchedule[$schedule->id] = $missingSiblings;
            }
        }

        $this->set(compact('schedules', 'user', 'activeScheduleId', 'childrenCounts', 'missingSiblingsPerSchedule'));
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
        if (!$this->hasOrgRole($schedule->organization_id, 'editor')) {
            $this->Flash->error(__('Sie haben keine Berechtigung Kinder zu verwalten.'));
            return $this->redirect(['action' => 'index']);
        }

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

        // Get all schedules for this organization (for dropdown)
        $schedules = $this->Schedules->find()
            ->where(['organization_id' => $schedule->organization_id])
            ->orderBy(['created' => 'DESC'])
            ->all();

        // Get children for this specific schedule (for right column - with organization_order)
        $childrenTable = $this->fetchTable('Children');
        $childrenInOrder = $childrenTable->find()
            ->where([
                'Children.schedule_id' => $schedule->id,
                'Children.organization_order IS NOT' => null
            ])
            ->contain(['SiblingGroups'])
            ->orderBy(['Children.organization_order' => 'ASC', 'Children.id' => 'ASC'])
            ->all();

        // Get all children of organization without organization_order (for left column - excluded)
        $childrenNotInOrder = $childrenTable->find()
            ->where([
                'Children.organization_id' => $schedule->organization_id,
                'Children.organization_order IS' => null
            ])
            ->contain(['SiblingGroups'])
            ->orderBy(['Children.name' => 'ASC'])
            ->all();

        // Find siblings assigned to different schedules
        $missingSiblings = $this->findMissingSiblings($childrenInOrder, $schedule->id);

        $this->set(compact('schedule', 'schedules', 'childrenInOrder', 'childrenNotInOrder', 'missingSiblings'));
    }

    /**
     * Remove child from organization order (set to NULL)
     *
     * @param string|null $id Schedule id
     * @return \Cake\Http\Response JSON response
     */
    public function removeFromOrder($id = null)
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $schedule = $this->Schedules->get($id);
        
        // Check permission
        if (!$this->hasOrgRole($schedule->organization_id, 'editor')) {
            $this->set([
                'success' => false,
                'message' => __('Permission denied'),
                '_serialize' => ['success', 'message']
            ]);
            return $this->response->withType('application/json');
        }
        
        $data = $this->request->getData();
        $childId = $data['child_id'] ?? null;
        
        if ($childId) {
            $childrenTable = $this->fetchTable('Children');
            $child = $childrenTable->get($childId);
            
            // Set organization_order to NULL
            $child->organization_order = null;
            
            if ($childrenTable->save($child)) {
                $this->set([
                    'success' => true,
                    'message' => __('Child removed from organization order'),
                    '_serialize' => ['success', 'message']
                ]);
            } else {
                $this->set([
                    'success' => false,
                    'message' => __('Failed to save'),
                    '_serialize' => ['success', 'message']
                ]);
            }
        } else {
            $this->set([
                'success' => false,
                'message' => __('Invalid child ID'),
                '_serialize' => ['success', 'message']
            ]);
        }
        
        return $this->response->withType('application/json');
    }

    /**
     * Add child to organization order (assign next order number)
     *
     * @param string|null $id Schedule id
     * @return \Cake\Http\Response JSON response
     */
    public function addToOrder($id = null)
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $schedule = $this->Schedules->get($id);
        
        // Check permission
        if (!$this->hasOrgRole($schedule->organization_id, 'editor')) {
            $this->set([
                'success' => false,
                'message' => __('Permission denied'),
                '_serialize' => ['success', 'message']
            ]);
            return $this->response->withType('application/json');
        }
        
        $data = $this->request->getData();
        $childId = $data['child_id'] ?? null;
        
        if ($childId) {
            $childrenTable = $this->fetchTable('Children');
            $child = $childrenTable->get($childId);
            
            // Find max organization_order and assign next number
            $maxOrderResult = $childrenTable->find()
                ->where([
                    'organization_id' => $schedule->organization_id,
                    'organization_order IS NOT' => null
                ])
                ->orderBy(['organization_order' => 'DESC'])
                ->first();
            
            $nextOrder = $maxOrderResult ? (int)$maxOrderResult->organization_order + 1 : 1;
            $child->organization_order = $nextOrder;
            $child->schedule_id = $schedule->id; // Set schedule_id when adding to order
            
            if ($childrenTable->save($child)) {
                $this->set([
                    'success' => true,
                    'message' => __('Child added to organization order'),
                    '_serialize' => ['success', 'message']
                ]);
            } else {
                $this->set([
                    'success' => false,
                    'message' => __('Failed to save'),
                    '_serialize' => ['success', 'message']
                ]);
            }
        } else {
            $this->set([
                'success' => false,
                'message' => __('Invalid child ID'),
                '_serialize' => ['success', 'message']
            ]);
        }
        
        return $this->response->withType('application/json');
    }

    /**
     * Update children organization_order after drag & drop
     *
     * @param string|null $id Schedule id
     * @return \Cake\Http\Response JSON response
     */
    public function updateChildrenOrder($id = null)
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $schedule = $this->Schedules->get($id);
        
        // Check permission
        if (!$this->hasOrgRole($schedule->organization_id, 'editor')) {
            $this->set([
                'success' => false,
                'message' => __('Permission denied'),
                '_serialize' => ['success', 'message']
            ]);
            return $this->response->withType('application/json');
        }
        
        $data = $this->request->getData();
        $childrenIds = $data['children'] ?? [];
        
        if (!empty($childrenIds) && is_array($childrenIds)) {
            $childrenTable = $this->fetchTable('Children');
            
            // Update organization_order for each child based on array position
            foreach ($childrenIds as $index => $childId) {
                $child = $childrenTable->get($childId);
                $child->organization_order = $index + 1; // Start from 1
                $childrenTable->save($child);
            }
            
            $this->set([
                'success' => true,
                'message' => __('Children order updated'),
                '_serialize' => ['success', 'message']
            ]);
        } else {
            $this->set([
                'success' => false,
                'message' => __('Invalid children data'),
                '_serialize' => ['success', 'message']
            ]);
        }
        
        return $this->response->withType('application/json');
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
     * Export CSV (using grid-based export)
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
        
        // Use SAME grid generation as HTML view
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        $gridService = new \App\Service\ReportGridService();
        $gridData = $gridService->generateGrid($reportData);
        
        // Convert grid to CSV
        $csv = [];
        
        // Header
        $csv[] = ['Ausfallplan', $schedule->title];
        $csv[] = ['Organisation', $schedule->organization->name];
        $csv[] = [''];
        
        // Grid rows - iterate through grid
        foreach ($gridData['grid'] as $row) {
            $csvRow = [];
            foreach ($row as $cell) {
                $value = $cell['value'];
                
                // Add special markers based on cell type
                switch ($cell['type']) {
                    case \App\Service\ReportGridService::CELL_CHILD:
                        if (isset($cell['metadata']['is_integrative']) && $cell['metadata']['is_integrative']) {
                            $value .= ' (I)';
                        }
                        break;
                    case \App\Service\ReportGridService::CELL_LEAVING:
                        $value = '→ ' . str_replace('→ ', '', $value);
                        break;
                }
                
                $csvRow[] = $value;
            }
            $csv[] = $csvRow;
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
