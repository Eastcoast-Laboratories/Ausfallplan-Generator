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
            $this->set(compact('children'));
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

        $this->set(compact('children'));
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

        $this->set(compact('child'));
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
        
        $this->set(compact('child', 'siblingGroups'));
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
}
