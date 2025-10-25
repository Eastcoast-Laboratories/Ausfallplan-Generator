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
        
        // System admins can see all schedules
        if ($user && $user->is_system_admin) {
            $schedulesTable = $this->fetchTable('Schedules');
            $schedules = $schedulesTable->find()
                ->orderBy(['Schedules.created' => 'DESC'])
                ->all();
            
            // Priority: Query param > Session > Schedule with assignments > First schedule
            $scheduleId = $this->request->getQuery('schedule_id');
            $selectedSchedule = null;
            
            // 1. Try query parameter first
            if ($scheduleId) {
                try {
                    $selectedSchedule = $schedulesTable->get($scheduleId);
                    // Update session when user manually selects a schedule
                    $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
                } catch (\Exception $e) {
                    $scheduleId = null;
                }
            }
            
            // 2. Try active schedule from session
            if (!$selectedSchedule) {
                $activeScheduleId = $this->request->getSession()->read('activeScheduleId');
                if ($activeScheduleId) {
                    try {
                        $selectedSchedule = $schedulesTable->get($activeScheduleId);
                        $scheduleId = $selectedSchedule->id;
                    } catch (\Exception $e) {
                        // Schedule doesn't exist anymore, clear from session
                        $this->request->getSession()->delete('activeScheduleId');
                    }
                }
            }
            
            // 3. Find schedule with assignments (prefer schedules with children)
            if (!$selectedSchedule) {
                foreach ($schedules as $schedule) {
                    // Check if this schedule has assignments
                    $hasAssignments = $this->fetchTable('Assignments')->find()
                        ->innerJoinWith('ScheduleDays')
                        ->where(['ScheduleDays.schedule_id' => $schedule->id])
                        ->count();
                    
                    if ($hasAssignments > 0) {
                        $selectedSchedule = $schedule;
                        $scheduleId = $schedule->id;
                        // Set as active in session
                        $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
                        break;
                    }
                }
            }
            
            // 4. Fallback to first schedule if no schedule has assignments
            if (!$selectedSchedule) {
                $selectedSchedule = $schedules->first();
                $scheduleId = $selectedSchedule ? $selectedSchedule->id : null;
                if ($scheduleId) {
                    // Set as active in session
                    $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
                }
            }
            
            // Load waitlist entries and available children (same logic as for regular users)
            $waitlistEntries = [];
            $availableChildren = [];
            $childrenInSchedule = [];
            $childrenOnWaitlist = [];
            
            if ($scheduleId) {
                // Get waitlist entries
                $waitlistEntries = $this->fetchTable('WaitlistEntries')->find()
                    ->where(['WaitlistEntries.schedule_id' => $scheduleId])
                    ->contain(['Children', 'Schedules'])
                    ->orderBy(['WaitlistEntries.priority' => 'ASC'])
                    ->all();
                
                // Get children that are assigned to any day in this schedule
                $assignments = $this->fetchTable('Assignments')->find()
                    ->select(['child_id' => 'DISTINCT Assignments.child_id'])
                    ->innerJoinWith('ScheduleDays')
                    ->where(['ScheduleDays.schedule_id' => $scheduleId])
                    ->all();
                
                foreach ($assignments as $assignment) {
                    $childrenInSchedule[] = $assignment->child_id;
                }
                
                // Get children already on waitlist
                foreach ($waitlistEntries as $entry) {
                    $childrenOnWaitlist[] = $entry->child_id;
                }
                
                // Available children: In schedule BUT NOT on waitlist
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
            }
            
            $countNotOnWaitlist = count($availableChildren);
            $siblingGroupsMap = [];
            $siblingNames = [];
            $missingSiblings = [];
            
            $this->set(compact('schedules', 'selectedSchedule', 'waitlistEntries', 'availableChildren', 'countNotOnWaitlist', 'siblingGroupsMap', 'siblingNames', 'missingSiblings', 'user'));
            return;
        }
        
        // Get user's primary organization
        $primaryOrg = $this->getPrimaryOrganization();
        if (!$primaryOrg) {
            $this->Flash->error(__('Sie sind keiner Organisation zugeordnet.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'logout']);
        }
        
        // Get schedules for this organization
        $schedulesTable = $this->fetchTable('Schedules');
        $schedules = $schedulesTable->find()
            ->where(['Schedules.organization_id' => $primaryOrg->id])
            ->orderBy(['Schedules.created' => 'DESC'])
            ->all();
        
        // Get selected schedule with priority: Query param > Session > First schedule
        $scheduleId = $this->request->getQuery('schedule_id');
        $selectedSchedule = null;
        
        // 1. Try query parameter
        if ($scheduleId) {
            try {
                $selectedSchedule = $schedulesTable->get($scheduleId);
                // Update session when user manually selects a schedule
                $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
            } catch (\Exception $e) {
                // Invalid schedule ID
                $scheduleId = null;
            }
        }
        // 2. Try active schedule from session
        if (!$selectedSchedule) {
            $activeScheduleId = $this->request->getSession()->read('activeScheduleId');
            if ($activeScheduleId) {
                try {
                    $selectedSchedule = $schedulesTable->get($activeScheduleId);
                    $scheduleId = $selectedSchedule->id;
                } catch (\Exception $e) {
                    // Schedule doesn't exist anymore, clear from session
                    $this->request->getSession()->delete('activeScheduleId');
                }
            }
        }
        // 3. Default to first schedule
        if (!$selectedSchedule && $schedules->count() > 0) {
            $selectedSchedule = $schedules->first();
            $scheduleId = $selectedSchedule->id;
            // Also set as active in session
            $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
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
        
        // Get children already on waitlist
        $childrenOnWaitlist = [];
        foreach ($waitlistEntries as $entry) {
            $childrenOnWaitlist[] = $entry->child_id;
        }
        
        // Build sibling groups map and load sibling names
        $siblingGroupsMap = [];
        $siblingNames = [];
        $missingSiblings = []; // Track siblings not in schedule
        
        foreach ($waitlistEntries as $entry) {
            if ($entry->child->sibling_group_id) {
                $siblingGroupsMap[$entry->id] = $entry->child->sibling_group_id;
                
                // Load all siblings for this group
                $siblings = $this->fetchTable('Children')->find()
                    ->where([
                        'sibling_group_id' => $entry->child->sibling_group_id,
                        'id !=' => $entry->child->id
                    ])
                    ->all();
                
                $names = [];
                foreach ($siblings as $sib) {
                    $names[] = $sib->name;
                    
                    // Check if sibling is in schedule
                    if (!in_array($sib->id, $childrenInSchedule)) {
                        $missingSiblings[] = [
                            'name' => $sib->name,
                            'sibling_of' => $entry->child->name,
                        ];
                    }
                }
                $siblingNames[$entry->child->id] = implode(', ', $names);
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
        
        // Count total children not yet on waitlist (for "Add All" button visibility)
        $childrenNotOnWaitlist = $this->fetchTable('Children')->find()
            ->where([
                'Children.organization_id' => $primaryOrg->id,
                'Children.is_active' => true
            ]);
        
        if (!empty($childrenOnWaitlist)) {
            $childrenNotOnWaitlist->where([
                'Children.id NOT IN' => $childrenOnWaitlist
            ]);
        }
        
        $countNotOnWaitlist = $childrenNotOnWaitlist->count();
        
        $this->set(compact('schedules', 'selectedSchedule', 'waitlistEntries', 'availableChildren', 'countNotOnWaitlist', 'siblingGroupsMap', 'siblingNames', 'missingSiblings'));
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
        
        // Load child to check for siblings
        $child = $this->fetchTable('Children')->get($childId);
        
        // Check if child has siblings already on waitlist
        $nextPriority = null;
        if ($child->sibling_group_id) {
            // Find sibling on waitlist with highest priority
            $siblingEntry = $this->fetchTable('WaitlistEntries')->find()
                ->contain(['Children'])
                ->where([
                    'WaitlistEntries.schedule_id' => $scheduleId,
                    'Children.sibling_group_id' => $child->sibling_group_id,
                    'Children.id !=' => $childId
                ])
                ->orderBy(['WaitlistEntries.priority' => 'DESC'])
                ->first();
            
            if ($siblingEntry) {
                // Place directly after sibling
                $nextPriority = $siblingEntry->priority + 1;
                
                // Shift all following entries down by 1
                $this->fetchTable('WaitlistEntries')->updateAll(
                    ['priority' => new \Cake\Database\Expression\QueryExpression('priority + 1')],
                    [
                        'schedule_id' => $scheduleId,
                        'priority >=' => $nextPriority
                    ]
                );
            }
        }
        
        // If no sibling found or no siblings, add at end
        if ($nextPriority === null) {
            $maxPriority = $this->fetchTable('WaitlistEntries')->find()
                ->where(['schedule_id' => $scheduleId])
                ->select(['max_priority' => 'MAX(priority)'])
                ->first();
            
            $nextPriority = ($maxPriority && $maxPriority->max_priority) ? $maxPriority->max_priority + 1 : 1;
        }
        
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

    /**
     * Add all available children to waitlist
     *
     * @param string|null $scheduleId Schedule ID
     * @return \Cake\Http\Response|null Redirects back to index
     */
    public function addAll($scheduleId = null)
    {
        $this->request->allowMethod(['post']);
        
        if (!$scheduleId) {
            $this->Flash->error(__('Invalid schedule.'));
            return $this->redirect(['action' => 'index']);
        }
        
        $user = $this->Authentication->getIdentity();
        $childrenTable = $this->fetchTable('Children');
        $waitlistTable = $this->fetchTable('WaitlistEntries');
        
        // Get user's primary organization (or all orgs for system admins)
        $primaryOrg = $this->getPrimaryOrganization();
        
        if (!$primaryOrg && !$user->is_system_admin) {
            $this->Flash->error(__('You must belong to an organization.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Get all active children for this organization
        $allChildrenQuery = $childrenTable->find()
            ->where(['Children.is_active' => true]);
        
        // System admins see all children, regular users only their org
        if (!$user->is_system_admin && $primaryOrg) {
            $allChildrenQuery->where(['Children.organization_id' => $primaryOrg->id]);
        }
        
        $allChildren = $allChildrenQuery->all();
        
        // Get existing waitlist entries - build array of child IDs
        $existingEntries = $waitlistTable->find()
            ->where(['schedule_id' => $scheduleId])
            ->all();
        
        $existingChildIds = [];
        foreach ($existingEntries as $entry) {
            $existingChildIds[] = (int)$entry->child_id;  // Cast to int to ensure type consistency
        }
        
        // Find next priority number
        $maxPriority = $waitlistTable->find()
            ->where(['schedule_id' => $scheduleId])
            ->select(['max_priority' => $waitlistTable->find()->func()->max('priority')])
            ->first();
        
        $nextPriority = ($maxPriority && isset($maxPriority->max_priority)) ? (int)$maxPriority->max_priority + 1 : 1;
        $addedCount = 0;
        
        // Add children that aren't already on the waitlist
        foreach ($allChildren as $child) {
            $childId = (int)$child->id;
            $isInArray = in_array($childId, $existingChildIds, true);
            
            if (!$isInArray) {
                $entry = $waitlistTable->newEntity([
                    'schedule_id' => (int)$scheduleId,
                    'child_id' => $childId,
                    'priority' => (int)$nextPriority
                ]);
                $nextPriority++;
                
                if ($waitlistTable->save($entry)) {
                    $addedCount++;
                }
            }
        }
        
        if ($addedCount > 0) {
            $this->Flash->success(__('Added {0} children to waitlist.', $addedCount));
        } else {
            $this->Flash->info(__('All children are already on the waitlist.'));
        }
        
        return $this->redirect(['action' => 'index', '?' => ['schedule_id' => $scheduleId]]);
    }
}
