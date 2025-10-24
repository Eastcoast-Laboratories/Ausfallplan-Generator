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
        // Get current user's organization
        $user = $this->Authentication->getIdentity();
        
        $children = $this->Children->find()
            ->where(['Children.organization_id' => $user->organization_id])
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
            
            // Set organization from authenticated user
            $user = $this->Authentication->getIdentity();
            $data['organization_id'] = $user->organization_id;
            
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
        
        $siblingGroups = $this->Children->SiblingGroups->find('list')
            ->where(['SiblingGroups.organization_id' => $this->Authentication->getIdentity()->organization_id])
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

        if ($this->request->is(['patch', 'post', 'put'])) {
            $child = $this->Children->patchEntity($child, $this->request->getData());
            
            if ($this->Children->save($child)) {
                $this->Flash->success(__('The child has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The child could not be updated. Please try again.'));
        }
        
        $siblingGroups = $this->Children->SiblingGroups->find('list')
            ->where(['SiblingGroups.organization_id' => $this->Authentication->getIdentity()->organization_id])
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
