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
        
        // Get user's organizations
        $userOrgs = $this->getUserOrganizations();
        $hasMultipleOrgs = count($userOrgs) > 1;
        
        // Get selected organization from query or session
        $selectedOrgId = $this->request->getQuery('organization_id');
        if ($selectedOrgId) {
            $this->request->getSession()->write('selectedOrgId', $selectedOrgId);
        } else {
            $selectedOrgId = $this->request->getSession()->read('selectedOrgId');
        }
        
        // System admin sees all schedules with user info
        if ($user && $user->is_system_admin) {
            $query = $this->Schedules->find()
                ->contain(['Organizations', 'Users'])
                ->orderBy(['Schedules.created' => 'DESC']);
            
            // Filter by organization if selected
            if ($selectedOrgId) {
                $query->where(['Schedules.organization_id' => $selectedOrgId]);
            }
            
            $schedules = $query->all();
        } else {
            // Regular users see schedules from their organization(s)
            $orgIds = array_map(fn($org) => $org->id, $userOrgs);
            
            if (!empty($orgIds)) {
                $query = $this->Schedules->find()
                    ->contain(['Organizations', 'Users'])
                    ->orderBy(['Schedules.created' => 'DESC']);
                
                // Filter by selected org or all user's orgs
                if ($selectedOrgId && in_array($selectedOrgId, $orgIds)) {
                    $query->where(['Schedules.organization_id' => $selectedOrgId]);
                } else {
                    $query->where(['Schedules.organization_id IN' => $orgIds]);
                }
                
                $schedules = $query->all();
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
        
        // Check if user is viewer in selected organization (for permission checks in view)
        $isViewer = false;
        if ($selectedOrgId) {
            $userRole = $this->getUserRoleInOrg((int)$selectedOrgId);
            $isViewer = ($userRole === 'viewer');
        }

        $this->set(compact('schedules', 'user', 'activeScheduleId', 'childrenCounts', 'missingSiblingsPerSchedule', 'userOrgs', 'hasMultipleOrgs', 'selectedOrgId', 'isViewer'));
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
        
        // Get user's organizations
        $userOrgs = $this->getUserOrganizations();
        $organizations = collection($userOrgs)
            ->combine('id', 'name')
            ->toArray();
        
        // Get selected organization from session (set by filter)
        $selectedOrgId = $this->request->getSession()->read('selectedOrgId');
        
        // If no selection and user has only one org, use it
        if (!$selectedOrgId && count($userOrgs) === 1) {
            $selectedOrgId = $userOrgs[0]->id;
        }
        
        // Default to first org if still no selection
        if (!$selectedOrgId && !empty($userOrgs)) {
            $selectedOrgId = $userOrgs[0]->id;
        }
        
        // Pre-select organization in form
        if ($selectedOrgId && !$schedule->organization_id) {
            $schedule->organization_id = $selectedOrgId;
        }
        
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
            
            // Check subscription limits for test plan users
            if (!$user->is_system_admin && $user->subscription_plan === 'test') {
                // Count active schedules for this user across all organizations
                $activeSchedulesCount = $this->Schedules->find()
                    ->where(['Schedules.user_id' => $user->id])
                    ->count();
                
                if ($activeSchedulesCount >= 1) {
                    $this->Flash->error(__('Test plan users can only create 1 schedule. Please upgrade to Pro for unlimited schedules.'));
                    $this->set(compact('schedule', 'organizations', 'canSelectOrganization'));
                    return;
                }
            }
            
            if ($this->Schedules->save($schedule)) {
                // Set this as the active schedule in session
                $this->request->getSession()->write('activeScheduleId', $schedule->id);
                
                // Check if days_count is a multiple of capacity_per_day (recommendation)
                if ($schedule->capacity_per_day > 0 && $schedule->days_count % $schedule->capacity_per_day !== 0) {
                    $this->Flash->warning(__('Empfehlung: Anzahl Tage sollte ein Vielfaches von "Max. Kinder pro Tag" ({0}) sein für optimale Verteilung.', $schedule->capacity_per_day));
                }
                
                $this->Flash->success(__('Schedule created successfully.'));
                return $this->redirect(['action' => 'view', $schedule->id]);
            }
            $this->Flash->error("[E84398] ".__('Could not save schedule.'));
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
        $schedule = $this->Schedules->get($id, contain: ['Organizations']);
        $user = $this->Authentication->getIdentity();
        
        // Permission check: User must be member of schedule's organization
        if (!$this->hasOrgRole($schedule->organization_id)) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Get user's organizations
        $userOrgs = $this->getUserOrganizations();
        $organizations = collection($userOrgs)
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
                
                // Check if days_count is a multiple of capacity_per_day (recommendation)
                if ($schedule->capacity_per_day > 0 && $schedule->days_count % $schedule->capacity_per_day !== 0) {
                    $this->Flash->warning(__('Empfehlung: Anzahl Tage sollte ein Vielfaches von "Max. Kinder pro Tag" ({0}) sein für optimale Verteilung.', $schedule->capacity_per_day));
                }
                
                $this->Flash->success(__('Schedule updated successfully.'));
                return $this->redirect(['action' => 'view', $schedule->id]);
            }
            $this->Flash->error(__('Could not save schedule.'));
        }
        
        $this->set(compact('schedule', 'organizations', 'userOrgs'));
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

        // Get children for this organization (for right column - with organization_order)
        $childrenTable = $this->fetchTable('Children');
        $childrenInOrder = $childrenTable->find()
            ->where([
                'Children.organization_id' => $schedule->organization_id,
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
        
        // Generate report data
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        // Build CSV with 4-day blocks
        $csv = [];
        
        // Header
        $csv[] = ['Ausfallplan', $schedule->title];
        $csv[] = ['Organisation', $schedule->organization->name];
        $csv[] = [''];
        
        // Split days into 4-day blocks
        $days = $reportData['days'];
        $waitlist = $reportData['waitlist'];
        $alwaysAtEnd = $reportData['alwaysAtEnd'];
        $dayBlocks = array_chunk($days, 4, true);
        
        // Calculate max rows needed
        $maxChildrenPerDay = 0;
        foreach ($days as $day) {
            $maxChildrenPerDay = max($maxChildrenPerDay, count($day['children'] ?? []));
        }
        $rowsPerBlock = max($maxChildrenPerDay + 3, 10);
        
        // Build each 4-day block
        foreach ($dayBlocks as $blockIndex => $blockDays) {
            $isFirstBlock = ($blockIndex === 0);
            
            // Block header - each day gets 2 columns (Name + Weight)
            $headerRow = [];
            foreach ($blockDays as $day) {
                $headerRow[] = $day['animalName'] . '-Tag ' . $day['number'];
                $headerRow[] = ''; // Zählkinder (Gewichtung)
            }
            if ($isFirstBlock) {
                $headerRow[] = ''; // Spacer
                $headerRow[] = __('Waitlist');
                $headerRow[] = ''; // Gewichtung
                $headerRow[] = 'D'; // Tage
                $headerRow[] = ''; // Nachrücken ⬇️
            }
            $csv[] = $headerRow;
            
            // Block content rows
            for ($rowIdx = 0; $rowIdx < $rowsPerBlock; $rowIdx++) {
                $row = [];
                
                // Day columns (each day: Name + Weight)
                foreach ($blockDays as $day) {
                    $children = $day['children'] ?? [];
                    if ($rowIdx < count($children)) {
                        $childData = $children[$rowIdx];
                        $row[] = $childData['child']->name;
                        $row[] = $childData['is_integrative'] ? 2 : 1;
                    } elseif ($rowIdx == count($children)) {
                        $firstOnWaitlist = $day['firstOnWaitlistChild'] ?? null;
                        $row[] = $firstOnWaitlist ? '→ ' . $firstOnWaitlist['child']->name : '';
                        $row[] = '';
                    } elseif ($rowIdx == count($children) + 1) {
                        $row[] = $day['countingChildrenSum'] ?? 0;
                        $row[] = '';
                    } else {
                        $row[] = '';
                        $row[] = '';
                    }
                }
                
                // Waitlist columns (only first block: Name + Weight + Days + Leaving)
                if ($isFirstBlock) {
                    $row[] = ''; // Spacer
                    
                    if ($rowIdx < count($waitlist)) {
                        $child = $waitlist[$rowIdx];
                        $childId = $child->id;
                        $stats = isset($childStats[$childId]) ? $childStats[$childId] : ['daysCount' => 0, 'firstOnWaitlistCount' => 0];
                        
                        $row[] = $child->name;
                        $row[] = $child->is_integrative ? 2 : 1;
                        $row[] = $stats['daysCount'];
                        $row[] = $stats['firstOnWaitlistCount'];
                    } elseif ($rowIdx == count($waitlist) + 1 && !empty($alwaysAtEnd)) {
                        $row[] = 'Immer am Ende:';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    } elseif ($rowIdx > count($waitlist) + 1 && !empty($alwaysAtEnd)) {
                        $alwaysAtEndIdx = $rowIdx - count($waitlist) - 2;
                        if ($alwaysAtEndIdx < count($alwaysAtEnd)) {
                            $childData = $alwaysAtEnd[$alwaysAtEndIdx];
                            $row[] = $childData['child']->name;
                            $row[] = $childData['weight'];
                            $row[] = '';
                            $row[] = '';
                        } else {
                            $row[] = '';
                            $row[] = '';
                            $row[] = '';
                            $row[] = '';
                        }
                    } else {
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    }
                }
                
                $csv[] = $row;
            }
            
            // Empty row between blocks
            if ($blockIndex < count($dayBlocks) - 1) {
                $csv[] = [];
            }
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
     * Export schedule as Excel (XLS) with checksums
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null
     */
    public function exportXls($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: ['Organizations']);
        
        if (!$schedule) {
            $this->Flash->error(__('Schedule not found.'));
            return $this->redirect(['action' => 'index']);
        }
        
        $childrenTable = $this->fetchTable('Children');
        $assignedChildrenCount = $childrenTable->find()
            ->where([
                'schedule_id' => $schedule->id,
                'waitlist_order IS NOT' => null
            ])
            ->count();
        
        $daysCount = $schedule->days_count ?? $assignedChildrenCount;
        
        // Generate report data
        $reportService = new \App\Service\ReportService();
        $reportData = $reportService->generateReportData((int)$id, $daysCount);
        
        $days = $reportData['days'];
        $waitlist = $reportData['waitlist'];
        $alwaysAtEnd = $reportData['alwaysAtEnd'];
        $childStats = $reportData['childStats'];
        $dayBlocks = array_chunk($days, 4, true);
        
        // Create Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($schedule->title, 0, 31)); // Excel max 31 chars for sheet name
        
        // Header
        $sheet->setCellValue('A1', $schedule->title);
        $sheet->setCellValue('A2', __('Organization'));
        $sheet->setCellValue('B2', $schedule->organization->name);
        
        $currentRow = 4;
        
        // Track border information for each block
        $blockBorderInfo = [];
        
        // PRE-CALCULATE first-on-waitlist rows for ALL blocks (needed for formulas)
        $firstOnWaitlistRows = [];
        $tempRow = $currentRow;
        foreach ($dayBlocks as $blockIndex => $blockDays) {
            $isFirstBlock = ($blockIndex === 0);
            
            // Calculate max children for this block
            $maxChildrenPerDay = 0;
            foreach ($blockDays as $day) {
                $maxChildrenPerDay = max($maxChildrenPerDay, count($day['children'] ?? []));
            }
            
            // Block structure: header + children + sum + first-on-waitlist
            $tempRow++; // Header row
            $tempRow += $maxChildrenPerDay; // Children rows
            $tempRow++; // Sum row
            $firstOnWaitlistRows[] = $tempRow; // This is the first-on-waitlist row
            $tempRow++; // Move to next row after first-on-waitlist
            
            // For first block: account for extra waitlist rows if needed
            if ($isFirstBlock) {
                $waitlistRows = count($waitlist);
                $alwaysAtEndRows = !empty($alwaysAtEnd) ? count($alwaysAtEnd) + 2 : 0;
                $totalWaitlistRows = $waitlistRows + $alwaysAtEndRows;
                $dayBlockRows = $maxChildrenPerDay + 2; // children + sum + first-on-waitlist
                
                // If waitlist needs more rows, add the difference
                if ($totalWaitlistRows > $dayBlockRows) {
                    $tempRow += ($totalWaitlistRows - $dayBlockRows);
                }
            }
            
            $tempRow++; // Spacer row between blocks
        }
        
        // Build each 4-day block
        foreach ($dayBlocks as $blockIndex => $blockDays) {
            $isFirstBlock = ($blockIndex === 0);
            $col = 1; // Column A
            
            // Track block start for border calculation
            $blockStartRow = $currentRow;
            
            // Block header - each day gets 2 columns (Name + Weight)
            foreach ($blockDays as $day) {
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $currentRow;
                $sheet->getCell($cellCoord)->setValue($day['animalName'] . '-Tag ' . $day['number']);
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $currentRow;
                $sheet->getCell($cellCoord)->setValue('');
                $col += 2;
            }
            
            if ($isFirstBlock) {
                $col++; // Spacer
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $currentRow;
                $sheet->getCell($cellCoord)->setValue(__('Nachrückliste'));
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $currentRow;
                $sheet->getCell($cellCoord)->setValue('');
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2) . $currentRow;
                $sheet->getCell($cellCoord)->setValue('D');
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 3) . $currentRow;
                $sheet->getCell($cellCoord)->setValue('⬇️');
                
            }
            
            $currentRow++;
            
            // Calculate max rows - all days must have same height
            $maxChildrenPerDay = 0;
            foreach ($blockDays as $day) {
                $maxChildrenPerDay = max($maxChildrenPerDay, count($day['children'] ?? []));
            }
            
            // Block structure (after header row):
            // 1. Children rows: maxChildrenPerDay rows (ALWAYS same height for all days)
            // 2. Sum row: 1 row
            // 3. First-on-waitlist row: 1 row
            // Total content rows: maxChildrenPerDay + 2
            $rowsPerBlock = $maxChildrenPerDay + 2;
            
            // For first block: may need more rows for waitlist + "Immer am Ende" children
            if ($isFirstBlock) {
                $waitlistRows = count($waitlist);
                $alwaysAtEndRows = !empty($alwaysAtEnd) ? count($alwaysAtEnd) + 2 : 0; // +2 for label and spacing
                $totalWaitlistRows = $waitlistRows + $alwaysAtEndRows;
                $rowsPerBlock = max($rowsPerBlock, $totalWaitlistRows);
            }
            
            // Calculate exact row numbers
            $firstChildRow = $currentRow;
            $lastChildRow = $currentRow + $maxChildrenPerDay - 1;
            $sumRowNum = $lastChildRow + 1;
            $firstOnWaitlistRowNum = $sumRowNum + 1;
            
            // Block content rows
            for ($rowIdx = 0; $rowIdx < $rowsPerBlock; $rowIdx++) {
                $col = 1;
                
                // Day columns (each day: Name + Weight)
                foreach ($blockDays as $dayIdx => $day) {
                    $children = $day['children'] ?? [];
                    
                    if ($rowIdx < $maxChildrenPerDay) {
                        // Children rows (ALWAYS maxChildrenPerDay rows)
                        if ($rowIdx < count($children)) {
                            $childData = $children[$rowIdx];
                            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $currentRow;
                            $sheet->getCell($cellCoord)->setValue($childData['child']->name);
                            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $currentRow;
                            $sheet->getCell($cellCoord)->setValue($childData['is_integrative'] ? 2 : 1);
                        }
                        // Else: empty row (to keep all days same height)
                    } elseif ($rowIdx == $maxChildrenPerDay) {
                        // Sum row - in weight column (col + 1) - small and gray
                        $weightCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
                        $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $currentRow;
                        $sheet->getCell($cellCoord)->setValue("=SUM({$weightCol}{$firstChildRow}:{$weightCol}{$lastChildRow})");
                        // Format: small font, gray color
                        $sheet->getStyle($cellCoord)->getFont()->setSize(8)->getColor()->setRGB('808080');
                    } elseif ($rowIdx == $maxChildrenPerDay + 1) {
                        // First on waitlist row - ALL in ONE row
                        // Name in column 0 (right-aligned), ⬇️ in column 1 (weight column)
                        $firstOnWaitlist = $day['firstOnWaitlistChild'] ?? null;
                        if ($firstOnWaitlist) {
                            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $currentRow;
                            $sheet->getCell($cellCoord)->setValue($firstOnWaitlist['child']->name);
                            // Right-align the name
                            $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                            // ⬇️ symbol in weight column
                            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $currentRow;
                            $sheet->getCell($cellCoord)->setValue('⬇️');
                        }
                    }
                    $col += 2;
                }
                
                // Waitlist columns (only first block: Name + Weight + Days + Leaving)
                if ($isFirstBlock) {
                    $col++; // Spacer
                    
                    if ($rowIdx < count($waitlist)) {
                        $child = $waitlist[$rowIdx];
                        $childId = $child->id;
                        $stats = isset($childStats[$childId]) ? $childStats[$childId] : ['daysCount' => 0, 'firstOnWaitlistCount' => 0];
                        
                        $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $currentRow;
                        $sheet->getCell($cellCoord)->setValue($child->name);
                        $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $currentRow;
                        $sheet->getCell($cellCoord)->setValue($child->is_integrative ? 2 : 1);
                        // D-Spalte: COUNTIF formula for days count - small and gray
                        $nameCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $firstOnWaitlistCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 3);
                        $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2) . $currentRow;
                        $sheet->getCell($cellCoord)->setValue("=COUNTIF(\$A\$5:\$G\$100,{$nameCol}{$currentRow})-{$firstOnWaitlistCol}{$currentRow}");
                        // Format: small font, gray color
                        $sheet->getStyle($cellCoord)->getFont()->setSize(8)->getColor()->setRGB('808080');
                        
                        // ⬇️-Spalte: COUNTIF formula for first on waitlist count - small and gray
                        $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 3) . $currentRow;
                        // Build formula dynamically based on tracked rows
                        $formulaParts = [];
                        foreach ($firstOnWaitlistRows as $fowRow) {
                            $formulaParts[] = "COUNTIF(\$A\${$fowRow}:\$G\${$fowRow},{$nameCol}{$currentRow})";
                        }
                        $firstOnWaitlistFormula = "=" . implode("+", $formulaParts);
                        $sheet->getCell($cellCoord)->setValue($firstOnWaitlistFormula);
                        // Format: small font, gray color
                        $sheet->getStyle($cellCoord)->getFont()->setSize(8)->getColor()->setRGB('808080');
                    } elseif ($rowIdx == count($waitlist) + 1 && !empty($alwaysAtEnd)) {
                        $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $currentRow;
                        $sheet->getCell($cellCoord)->setValue(__('Immer am Ende') . ':');
                    } elseif ($rowIdx > count($waitlist) + 1 && !empty($alwaysAtEnd)) {
                        $alwaysAtEndIdx = $rowIdx - count($waitlist) - 2;
                        if ($alwaysAtEndIdx < count($alwaysAtEnd)) {
                            $childData = $alwaysAtEnd[$alwaysAtEndIdx];
                            $childId = $childData['child']->id;
                            $stats = isset($childStats[$childId]) ? $childStats[$childId] : ['daysCount' => 0, 'firstOnWaitlistCount' => 0];
                            
                            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $currentRow;
                            $sheet->getCell($cellCoord)->setValue($childData['child']->name);
                            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $currentRow;
                            $sheet->getCell($cellCoord)->setValue($childData['weight']);
                            // D-Spalte: COUNTIF formula for days count ("Always at end") - small and gray
                            $nameCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                            $firstOnWaitlistCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 3);
                            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 2) . $currentRow;
                            $sheet->getCell($cellCoord)->setValue("=COUNTIF(\$A\$5:\$G\$100,{$nameCol}{$currentRow})-{$firstOnWaitlistCol}{$currentRow}");
                            // Format: small font, gray color
                            $sheet->getStyle($cellCoord)->getFont()->setSize(8)->getColor()->setRGB('808080');
                            
                            // ⬇️-Spalte: COUNTIF formula for first on waitlist count - small and gray
                            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 3) . $currentRow;
                            // Build formula dynamically based on tracked rows
                            $formulaParts = [];
                            foreach ($firstOnWaitlistRows as $fowRow) {
                                $formulaParts[] = "COUNTIF(\$A\${$fowRow}:\$G\${$fowRow},{$nameCol}{$currentRow})";
                            }
                            $firstOnWaitlistFormula = "=" . implode("+", $formulaParts);
                            $sheet->getCell($cellCoord)->setValue($firstOnWaitlistFormula);
                            // Format: small font, gray color
                            $sheet->getStyle($cellCoord)->getFont()->setSize(8)->getColor()->setRGB('808080');
                        }
                    }
                    
                    
                }
                
                $currentRow++;
            }
            
            // Store border info for this block
            $blockBorderInfo[] = [
                'startRow' => $blockStartRow,
                'endRow' => $currentRow - 1, // Current row - 1 is the last row of content
                'endCol' => count($blockDays) * 2,
                'isFirstBlock' => $isFirstBlock,
            ];
            
            $currentRow++; // Empty row between blocks
        }
        
        // Auto-size columns
        foreach (range(1, $col + 5) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }
        
        // Apply borders only at outer edges of blocks (not around every cell)
        // Border style
        $outerBorderStyle = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        // Apply borders using tracked information
        foreach ($blockBorderInfo as $borderInfo) {
            // Border around 4-day block
            $blockRange = 'A' . $borderInfo['startRow'] . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($borderInfo['endCol']) . $borderInfo['endRow'];
            $sheet->getStyle($blockRange)->applyFromArray($outerBorderStyle);
            
            // Border around waitlist (only first block)
            if ($borderInfo['isFirstBlock']) {
                $waitlistStartCol = $borderInfo['endCol'] + 2; // After spacer
                $waitlistEndCol = $waitlistStartCol + 3; // Name + Z + D + ⬇️
                // Waitlist border should match the actual content height
                $waitlistEndRow = $borderInfo['endRow'];
                
                $waitlistRange = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($waitlistStartCol) . $borderInfo['startRow'] . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($waitlistEndCol) . $waitlistEndRow;
                $sheet->getStyle($waitlistRange)->applyFromArray($outerBorderStyle);
            }
        }
        
        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        $filename = 'ausfallplan_' . $schedule->id . '.xls';
        
        // Output to browser
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    /**
     * Debug: Show "always at end" children for a schedule
     *
     * @param string|null $id Schedule id
     * @return \Cake\Http\Response
     */
    public function debugAlwaysAtEnd($id = null)
    {
        $schedule = $this->Schedules->get($id);
        
        $childrenTable = $this->fetchTable('Children');
        
        // All children with this schedule_id
        $allChildren = $childrenTable->find()
            ->where(['schedule_id' => $schedule->id])
            ->all()
            ->toArray();
        
        // Children WITH waitlist_order
        $withWaitlist = $childrenTable->find()
            ->where([
                'schedule_id' => $schedule->id,
                'waitlist_order IS NOT' => null
            ])
            ->all()
            ->toArray();
        
        // Children WITHOUT waitlist_order (should be "always at end")
        $withoutWaitlist = $childrenTable->find()
            ->where([
                'schedule_id' => $schedule->id,
                'waitlist_order IS' => null
            ])
            ->all()
            ->toArray();
        
        $debug = [
            'schedule_id' => $schedule->id,
            'schedule_title' => $schedule->title,
            'all_children_count' => count($allChildren),
            'with_waitlist_count' => count($withWaitlist),
            'without_waitlist_count' => count($withoutWaitlist),
            'all_children' => array_map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'schedule_id' => $c->schedule_id,
                'waitlist_order' => $c->waitlist_order,
                'organization_order' => $c->organization_order
            ], $allChildren),
            'with_waitlist' => array_map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'waitlist_order' => $c->waitlist_order
            ], $withWaitlist),
            'without_waitlist' => array_map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'waitlist_order' => $c->waitlist_order
            ], $withoutWaitlist)
        ];
        
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($debug, JSON_PRETTY_PRINT));
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
    
    /**
     * Build COUNTIF formula for first-on-waitlist count
     * 
     * @param string $nameCell Cell reference (e.g., "M5")
     * @param int $blockCount Number of 4-day blocks
     * @return string Excel formula
     */
    private function buildFirstOnWaitlistFormula(string $nameCell, int $blockCount): string
    {
        // This formula is NOT needed anymore!
        // The first-on-waitlist rows are now tracked during block generation
        // We'll build the formula dynamically based on actual row numbers
        
        // For now, return a simple formula that will be replaced
        // The actual implementation will be done inline during block generation
        return "=0";
    }
}
