<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Children Controller
 *
 * @property \App\Model\Table\ChildrenTable $Children
 */
class ChildrenController extends AppController
{
    /**
     * Index method - List all children
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $user = $this->Authentication->getIdentity();
        
        // System admins have no organization - show empty list or redirect
        if ($user && $user->is_system_admin) {
            // Admins can see all children across all organizations
            $children = $this->Children->find()
                ->contain(['Organizations', 'SiblingGroups'])
                ->orderBy(['Children.is_active' => 'DESC', 'Children.name' => 'ASC'])
                ->all();
            
            // Load sibling names for children with siblings
            $siblingNames = $this->loadSiblingNames($children);
            
            $this->set(compact('children', 'siblingNames'));
            return;
        }
        
        // Get user's primary organization
        $primaryOrg = $this->getPrimaryOrganization();
        if (!$primaryOrg) {
            $this->Flash->error(__('Sie sind keiner Organisation zugeordnet.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'logout']);
        }
        
        $children = $this->Children->find()
            ->where(['Children.organization_id' => $primaryOrg->id])
            ->contain(['Organizations', 'SiblingGroups'])
            ->orderBy(['Children.is_active' => 'DESC', 'Children.name' => 'ASC'])
            ->all();
        
        // Load sibling names for children with siblings
        $siblingNames = $this->loadSiblingNames($children);

        $this->set(compact('children', 'siblingNames'));
    }

    /**
     * View method - Display a single child
     *
     * @param string|null $id Child id.
     * @return \Cake\Http\Response|null|void
     */
    public function view($id = null)
    {
        $child = $this->Children->get($id, contain: [
            'Organizations',
            'SiblingGroups',
            'Assignments',
            'WaitlistEntries',
        ]);
        
        // Permission check: User must be member of child's organization
        if (!$this->hasOrgRole($child->organization_id)) {
            $this->Flash->error(__('Zugriff verweigert.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Load sibling names if child has siblings
        $siblingNames = $this->loadSiblingNames([$child]);

        $this->set(compact('child', 'siblingNames'));
    }

    /**
     * Add method - Create a new child
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add()
    {
        $child = $this->Children->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Set organization from user's primary organization
            $user = $this->Authentication->getIdentity();
            
            $primaryOrg = $this->getPrimaryOrganization();
            if (!$primaryOrg) {
                $this->Flash->error(__('Sie müssen einer Organisation angehören, um Kinder zu erstellen.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $data['organization_id'] = $primaryOrg->id;
            
            // Set defaults
            if (!isset($data['is_active'])) {
                $data['is_active'] = true;
            }
            if (!isset($data['is_integrative'])) {
                $data['is_integrative'] = false;
            }
            
            $child = $this->Children->patchEntity($child, $data);
            
            if ($this->Children->save($child)) {
                // Check if there's an active schedule in session
                $activeScheduleId = $this->request->getSession()->read('activeScheduleId');
                
                if ($activeScheduleId) {
                    // Automatically assign child to active schedule
                    $this->autoAssignToSchedule($child->id, $activeScheduleId);
                }
                
                $this->Flash->success(__('The child has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The child could not be saved. Please try again.'));
        }
        
        // Get sibling groups from user's primary organization
        $primaryOrg = $this->getPrimaryOrganization();
        $siblingGroups = $this->Children->SiblingGroups->find('list')
            ->where(['SiblingGroups.organization_id' => $primaryOrg ? $primaryOrg->id : 0])
            ->all();
        
        $this->set(compact('child', 'siblingGroups'));
    }

    /**
     * Edit method - Update an existing child
     *
     * @param string|null $id Child id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     */
    public function edit($id = null)
    {
        $child = $this->Children->get($id, contain: []);
        
        // Permission check: User must have editor role in child's organization
        if (!$this->hasOrgRole($child->organization_id, 'editor')) {
            $this->Flash->error(__('Sie haben keine Berechtigung Kinder zu bearbeiten.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $child = $this->Children->patchEntity($child, $this->request->getData());
            
            if ($this->Children->save($child)) {
                $this->Flash->success(__('The child has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The child could not be updated. Please try again.'));
        }
        
        // Get sibling groups from child's organization
        $siblingGroups = $this->Children->SiblingGroups->find('list')
            ->where(['SiblingGroups.organization_id' => $child->organization_id ?? 0])
            ->all();
        
        // Load sibling names if child has siblings
        $siblingNames = $this->loadSiblingNames([$child]);
        
        $this->set(compact('child', 'siblingGroups', 'siblingNames'));
    }

    /**
     * Delete method - Remove a child
     *
     * @param string|null $id Child id.
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $child = $this->Children->get($id);
        
        // Permission check: User must have editor role in child's organization
        if (!$this->hasOrgRole($child->organization_id, 'editor')) {
            $this->Flash->error(__('Sie haben keine Berechtigung Kinder zu löschen.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($this->Children->delete($child)) {
            $this->Flash->success(__('The child has been deleted.'));
        } else {
            $this->Flash->error(__('The child could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Automatically assign a child to a schedule
     *
     * @param int $childId Child ID
     * @param int $scheduleId Schedule ID
     * @return void
     */
    private function autoAssignToSchedule(int $childId, int $scheduleId): void
    {
        $scheduleDaysTable = $this->fetchTable('ScheduleDays');
        $assignmentsTable = $this->fetchTable('Assignments');
        
        // Get all schedule days for this schedule
        $scheduleDays = $scheduleDaysTable->find()
            ->where(['schedule_id' => $scheduleId])
            ->all();
        
        // Get max sort_order for this schedule
        $maxSortOrder = $assignmentsTable->find()
            ->select(['max_sort' => 'MAX(sort_order)'])
            ->innerJoinWith('ScheduleDays')
            ->where(['ScheduleDays.schedule_id' => $scheduleId])
            ->first();
        
        $nextSortOrder = ($maxSortOrder && $maxSortOrder->max_sort) ? $maxSortOrder->max_sort + 1 : 1;
        
        // Create assignments for all days
        foreach ($scheduleDays as $scheduleDay) {
            // Check if assignment already exists
            $existingAssignment = $assignmentsTable->find()
                ->where([
                    'schedule_day_id' => $scheduleDay->id,
                    'child_id' => $childId
                ])
                ->first();
            
            if (!$existingAssignment) {
                $assignment = $assignmentsTable->newEntity([
                    'schedule_day_id' => $scheduleDay->id,
                    'child_id' => $childId,
                    'weight' => 1, // Default weight
                    'sort_order' => $nextSortOrder,
                ]);
                $assignmentsTable->save($assignment);
            }
        }
    }

    /**
     * Import method - Upload CSV and show preview
     *
     * @return \Cake\Http\Response|null|void
     */
    public function import()
    {
        $user = $this->Authentication->getIdentity();
        
        // Get user's primary organization
        $primaryOrg = $this->getPrimaryOrganization();
        if (!$primaryOrg && !$user->is_system_admin) {
            $this->Flash->error(__('Sie müssen einer Organisation angehören, um Kinder zu importieren.'));
            return $this->redirect(['action' => 'index']);
        }
        
        if ($this->request->is('post')) {
            $file = $this->request->getData('csv_file');
            
            if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
                $this->Flash->error(__('Bitte wählen Sie eine gültige CSV-Datei aus.'));
                return;
            }
            
            try {
                // Parse CSV
                $filePath = $file->getStream()->getMetadata('uri');
                $importService = new \App\Service\CsvImportService();
                $parsedChildren = $importService->parseCsv($filePath);
                
                if (empty($parsedChildren)) {
                    $this->Flash->warning(__('Keine gültigen Einträge in der CSV-Datei gefunden.'));
                    return;
                }
                
                // Store parsed data in session for confirmation step
                $this->request->getSession()->write('import_data', $parsedChildren);
                $this->request->getSession()->write('import_org_id', $primaryOrg->id ?? 1);
                
                // Redirect to preview
                return $this->redirect(['action' => 'importPreview']);
                
            } catch (\Exception $e) {
                $this->Flash->error(__('Fehler beim Lesen der Datei: {0}', $e->getMessage()));
                return;
            }
        }
        
        $this->set(compact('primaryOrg'));
    }

    /**
     * Import Preview - Show parsed data and anonymization options
     *
     * @return \Cake\Http\Response|null|void
     */
    public function importPreview()
    {
        $parsedChildren = $this->request->getSession()->read('import_data');
        
        if (empty($parsedChildren)) {
            $this->Flash->error(__('Keine Import-Daten gefunden. Bitte laden Sie zuerst eine CSV-Datei hoch.'));
            return $this->redirect(['action' => 'import']);
        }
        
        // Group by siblings for display
        $siblingGroups = [];
        foreach ($parsedChildren as $child) {
            if ($child['sibling_group_id']) {
                $siblingGroups[$child['sibling_group_id']][] = $child;
            }
        }
        
        $this->set(compact('parsedChildren', 'siblingGroups'));
    }

    /**
     * Import Confirm - Save children to database
     *
     * @return \Cake\Http\Response|null|void
     */
    public function importConfirm()
    {
        $this->request->allowMethod(['post']);
        
        $parsedChildren = $this->request->getSession()->read('import_data');
        $orgId = $this->request->getSession()->read('import_org_id');
        
        if (empty($parsedChildren)) {
            $this->Flash->error(__('Keine Import-Daten gefunden.'));
            return $this->redirect(['action' => 'import']);
        }
        
        $anonymizationMode = $this->request->getData('anonymization_mode', 'full');
        $importService = new \App\Service\CsvImportService();
        $siblingGroupsTable = $this->fetchTable('SiblingGroups');
        
        $imported = 0;
        $skipped = [];
        $errors = [];
        $siblingGroupMap = []; // Map import sibling_group_id to real DB id
        
        // First pass: Determine sibling group names
        $siblingGroupNames = [];
        foreach ($parsedChildren as $childData) {
            if ($childData['sibling_group_id']) {
                $groupId = $childData['sibling_group_id'];
                if (!isset($siblingGroupNames[$groupId])) {
                    $siblingGroupNames[$groupId] = [];
                }
                $siblingGroupNames[$groupId][] = $childData['last_name'];
            }
        }
        
        // Generate group names
        foreach ($siblingGroupNames as $groupId => $lastNames) {
            $uniqueLastNames = array_unique($lastNames);
            if (count($uniqueLastNames) === 1) {
                // Same last name for all siblings
                $siblingGroupNames[$groupId] = $uniqueLastNames[0];
            } else {
                // Different last names: combine with hyphen
                $siblingGroupNames[$groupId] = implode('-', $uniqueLastNames);
            }
        }
        
        foreach ($parsedChildren as $childData) {
            // Apply anonymization for display name (used for checking duplicates and display)
            $displayName = $importService->anonymizeName($childData, $anonymizationMode);
            
            // Check if child already exists
            $existingChild = $this->Children->find()
                ->where([
                    'name' => $displayName,
                    'organization_id' => $orgId,
                ])
                ->first();
            
            if ($existingChild) {
                $skipped[]=$displayName;
                continue;
            }
            
            // Handle sibling groups
            $dbSiblingGroupId = null;
            if ($childData['sibling_group_id']) {
                if (!isset($siblingGroupMap[$childData['sibling_group_id']])) {
                    // Create new sibling group with name
                    $groupName = $siblingGroupNames[$childData['sibling_group_id']] ?? 'Geschwistergruppe';
                    $siblingGroup = $siblingGroupsTable->newEntity([
                        'organization_id' => $orgId,
                        'label' => $groupName,
                    ]);
                    $siblingGroupsTable->save($siblingGroup);
                    $siblingGroupMap[$childData['sibling_group_id']] = $siblingGroup->id;
                }
                $dbSiblingGroupId = $siblingGroupMap[$childData['sibling_group_id']];
            }
            
            // Create child
            $child = $this->Children->newEntity([
                'organization_id' => $orgId,
                'name' => $displayName, // Anonymized display name
                'last_name' => $childData['last_name'], // Always save real last name
                'birth_date' => $childData['birth_date'],
                'gender' => $childData['gender'],
                'is_integrative' => $childData['is_integrative'],
                'postal_code' => $childData['postal_code'],
                'sibling_group_id' => $dbSiblingGroupId,
                'is_active' => true,
            ]);
            
            if ($this->Children->save($child)) {
                $imported++;
            } else {
                $errors[] = $displayName . ': ' . implode(', ', $child->getErrors());
                $skipped[]=$displayName;
            }
        }
        
        // Clear session data
        $this->request->getSession()->delete('import_data');
        $this->request->getSession()->delete('import_org_id');
        
        // Show results
        if ($imported > 0) {
            $this->Flash->success(__('Erfolgreich {0} Kinder importiert.', $imported));
        }
        if (count($skipped) > 0) {
            $this->Flash->warning(__('{0} Einträge übersprungen (bereits vorhanden oder ungültig);' . implode(", ", $skipped), count($skipped)));
        }
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->Flash->error($error);
            }
        }
        
        return $this->redirect(['action' => 'index']);
    }
    
    /**
     * Load sibling names for a collection of children
     *
     * @param iterable $children Children to load sibling names for
     * @return array Map of child_id => comma-separated sibling names
     */
    private function loadSiblingNames(iterable $children): array
    {
        $siblingNames = [];
        
        foreach ($children as $child) {
            if ($child->sibling_group_id && !isset($siblingNames[$child->id])) {
                // CRITICAL: Check total count in group FIRST
                $totalInGroup = $this->fetchTable('Children')->find()
                    ->where(['sibling_group_id' => $child->sibling_group_id])
                    ->count();
                
                // ERROR: A sibling group with only 1 child is a DATA ERROR!
                if ($totalInGroup <= 1) {
                    error_log("ERROR: Child '{$child->name}' (ID: {$child->id}) has sibling_group_id {$child->sibling_group_id} but is ALONE in that group! This is a data integrity error.");
                    // SKIP this child - do NOT show sibling badge
                    continue;
                }
                
                $siblings = $this->fetchTable('Children')->find()
                    ->where([
                        'sibling_group_id' => $child->sibling_group_id,
                        'id !=' => $child->id
                    ])
                    ->orderBy(['name' => 'ASC'])
                    ->all();
                
                $names = [];
                foreach ($siblings as $sib) {
                    $names[] = $sib->name;
                }
                
                // Should never be empty due to count check
                $siblingNames[$child->id] = implode(', ', $names);
            }
        }
        
        return $siblingNames;
    }
}
