<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Waitlist Controller
 *
 * Manages the waitlist for schedules with drag & drop reordering
 *
 * @property \App\Model\Table\WaitlistEntriesTable $WaitlistEntries
 */
class WaitlistController extends AppController
{
    /**
     * Index method - List all waitlist entries for a schedule
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        // Get current user's organization
        $user = $this->Authentication->getIdentity();
        
        // Get schedules for this organization
        $schedulesTable = $this->fetchTable('Schedules');
        $schedules = $schedulesTable->find()
            ->where(['Schedules.organization_id' => $user->organization_id])
            ->orderBy(['Schedules.created' => 'DESC'])
            ->all();
        
        // Get selected schedule (default to first)
        $scheduleId = $this->request->getQuery('schedule_id');
        $selectedSchedule = null;
        
        if ($scheduleId) {
            $selectedSchedule = $schedulesTable->get($scheduleId);
        } elseif ($schedules->count() > 0) {
            $selectedSchedule = $schedules->first();
            $scheduleId = $selectedSchedule->id;
        }
        
        // Get waitlist entries for selected schedule
        $waitlistEntries = [];
        if ($scheduleId) {
            $waitlistEntries = $this->fetchTable('WaitlistEntries')->find()
                ->where(['WaitlistEntries.schedule_id' => $scheduleId])
                ->contain(['Children', 'Schedules'])
                ->orderBy(['WaitlistEntries.priority' => 'ASC'])
                ->all();
        }
        
        // Get children already on waitlist
        $childrenOnWaitlist = [];
        foreach ($waitlistEntries as $entry) {
            $childrenOnWaitlist[] = $entry->child_id;
        }
        
        // Get children that are assigned to any day in this schedule
        $childrenInSchedule = [];
        if ($scheduleId) {
            $assignments = $this->fetchTable('Assignments')->find()
                ->select(['child_id' => 'DISTINCT Assignments.child_id'])
                ->innerJoinWith('ScheduleDays')
                ->where(['ScheduleDays.schedule_id' => $scheduleId])
                ->all();
            
            foreach ($assignments as $assignment) {
                $childrenInSchedule[] = $assignment->child_id;
            }
        }
        
        // Available children: In schedule BUT NOT on waitlist
        $availableChildren = [];
        if (!empty($childrenInSchedule)) {
            $availableChildrenQuery = $this->fetchTable('Children')->find()
                ->where([
                    'Children.id IN' => $childrenInSchedule,
                    'Children.is_active' => true,
                ])
                ->orderBy(['Children.name' => 'ASC']);
            
            if (!empty($childrenOnWaitlist)) {
                $availableChildrenQuery->where([
                    'Children.id NOT IN' => $childrenOnWaitlist
                ]);
            }
            
            $availableChildren = $availableChildrenQuery->all();
        }
        
        $this->set(compact('schedules', 'selectedSchedule', 'waitlistEntries', 'availableChildren'));
    }

    /**
     * Add child to waitlist
     *
     * @return \Cake\Http\Response|null Redirects back to index
     */
    public function add()
    {
        $this->request->allowMethod(['post']);
        
        $data = $this->request->getData();
        $scheduleId = $data['schedule_id'];
        $childId = $data['child_id'];
        
        // Get next priority
        $maxPriority = $this->fetchTable('WaitlistEntries')->find()
            ->where(['schedule_id' => $scheduleId])
            ->select(['max_priority' => 'MAX(priority)'])
            ->first();
        
        $nextPriority = ($maxPriority && $maxPriority->max_priority) ? $maxPriority->max_priority + 1 : 1;
        
        $entry = $this->fetchTable('WaitlistEntries')->newEntity([
            'schedule_id' => $scheduleId,
            'child_id' => $childId,
            'priority' => $nextPriority,
        ]);
        
        if ($this->fetchTable('WaitlistEntries')->save($entry)) {
            $this->Flash->success(__('Child added to waitlist.'));
        } else {
            $this->Flash->error(__('Could not add child to waitlist.'));
        }
        
        return $this->redirect(['action' => 'index', '?' => ['schedule_id' => $scheduleId]]);
    }

    /**
     * Remove child from waitlist
     *
     * @param string|null $id Waitlist Entry id
     * @return \Cake\Http\Response|null Redirects back to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        
        $entry = $this->fetchTable('WaitlistEntries')->get($id);
        $scheduleId = $entry->schedule_id;
        
        if ($this->fetchTable('WaitlistEntries')->delete($entry)) {
            // Reorder remaining entries
            $this->reorderPriorities($scheduleId);
            $this->Flash->success(__('Child removed from waitlist.'));
        } else {
            $this->Flash->error(__('Could not remove child from waitlist.'));
        }
        
        return $this->redirect(['action' => 'index', '?' => ['schedule_id' => $scheduleId]]);
    }

    /**
     * Update priority order via AJAX (for drag & drop)
     *
     * @return \Cake\Http\Response JSON response
     */
    public function reorder()
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $data = $this->request->getData();
        $scheduleId = $data['schedule_id'];
        $order = $data['order']; // Array of entry IDs in new order
        
        $table = $this->fetchTable('WaitlistEntries');
        
        // Update priorities based on new order
        foreach ($order as $index => $entryId) {
            $entry = $table->get($entryId);
            $entry->priority = $index + 1;
            $table->save($entry);
        }
        
        $this->set([
            'success' => true,
            'message' => __('Waitlist order updated.'),
            '_serialize' => ['success', 'message']
        ]);
        
        return $this->response->withType('application/json');
    }

    /**
     * Reorder priorities after deletion to remove gaps
     *
     * @param int $scheduleId Schedule ID
     * @return void
     */
    private function reorderPriorities(int $scheduleId): void
    {
        $entries = $this->fetchTable('WaitlistEntries')->find()
            ->where(['schedule_id' => $scheduleId])
            ->orderBy(['priority' => 'ASC'])
            ->all();
        
        $priority = 1;
        foreach ($entries as $entry) {
            $entry->priority = $priority++;
            $this->fetchTable('WaitlistEntries')->save($entry);
        }
    }
}
