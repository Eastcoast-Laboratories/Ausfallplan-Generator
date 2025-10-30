<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Waitlist Controller - NEW ARCHITECTURE
 *
 * Uses children table fields directly:
 * - children.schedule_id
 * - children.waitlist_order
 * 
 * No separate waitlist_entries table!
 */
class WaitlistController extends AppController
{
    /**
     * Index method - List all children on waitlist for a schedule
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $user = $this->Authentication->getIdentity();
        
        // System admins can see all schedules
        if ($user && $user->is_system_admin) {
            $schedulesTable = $this->fetchTable('Schedules');
            $schedules = $schedulesTable->find()
                ->orderBy(['Schedules.created' => 'DESC'])
                ->all();
            
            // Get selected schedule (query param > session > first)
            $scheduleId = $this->request->getQuery('schedule_id');
            $selectedSchedule = null;
            
            if ($scheduleId) {
                try {
                    $selectedSchedule = $schedulesTable->get($scheduleId);
                    $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
                } catch (\Exception $e) {
                    $scheduleId = null;
                }
            }
            
            if (!$selectedSchedule) {
                $activeScheduleId = $this->request->getSession()->read('activeScheduleId');
                if ($activeScheduleId) {
                    try {
                        $selectedSchedule = $schedulesTable->get($activeScheduleId);
                        $scheduleId = $selectedSchedule->id;
                    } catch (\Exception $e) {
                        $this->request->getSession()->delete('activeScheduleId');
                    }
                }
            }
            
            // Find schedule with waitlist children
            if (!$selectedSchedule) {
                foreach ($schedules as $schedule) {
                    $hasChildren = $this->fetchTable('Children')->find()
                        ->where([
                            'schedule_id' => $schedule->id,
                            'waitlist_order IS NOT' => null
                        ])
                        ->count();
                    
                    if ($hasChildren > 0) {
                        $selectedSchedule = $schedule;
                        $scheduleId = $schedule->id;
                        $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
                        break;
                    }
                }
            }
            
            // Fallback to first schedule
            if (!$selectedSchedule) {
                $selectedSchedule = $schedules->first();
                $scheduleId = $selectedSchedule ? $selectedSchedule->id : null;
                if ($scheduleId) {
                    $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
                }
            }
        } else {
            // Regular user: Get their organization
            $primaryOrg = $this->getPrimaryOrganization();
            if (!$primaryOrg) {
                $this->Flash->error(__('Sie sind keiner Organisation zugeordnet.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'logout']);
            }
            
            $schedulesTable = $this->fetchTable('Schedules');
            $schedules = $schedulesTable->find()
                ->where(['Schedules.organization_id' => $primaryOrg->id])
                ->orderBy(['Schedules.created' => 'DESC'])
                ->all();
            
            $scheduleId = $this->request->getQuery('schedule_id');
            $selectedSchedule = null;
            
            if ($scheduleId) {
                try {
                    $selectedSchedule = $schedulesTable->get($scheduleId);
                    $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
                } catch (\Exception $e) {
                    $scheduleId = null;
                }
            }
            
            if (!$selectedSchedule) {
                $activeScheduleId = $this->request->getSession()->read('activeScheduleId');
                if ($activeScheduleId) {
                    try {
                        $selectedSchedule = $schedulesTable->get($activeScheduleId);
                        $scheduleId = $selectedSchedule->id;
                    } catch (\Exception $e) {
                        $this->request->getSession()->delete('activeScheduleId');
                    }
                }
            }
            
            if (!$selectedSchedule && $schedules->count() > 0) {
                $selectedSchedule = $schedules->first();
                $scheduleId = $selectedSchedule->id;
                $this->request->getSession()->write('activeScheduleId', (int)$scheduleId);
            }
        }
        
        // Get children on waitlist (NEW: from children table)
        $waitlistChildren = [];
        $childrenOnWaitlist = [];
        if ($scheduleId) {
            $waitlistChildren = $this->fetchTable('Children')->find()
                ->where([
                    'schedule_id' => $scheduleId,
                    'waitlist_order IS NOT' => null
                ])
                ->orderBy(['waitlist_order' => 'ASC'])
                ->all();
            
            foreach ($waitlistChildren as $child) {
                $childrenOnWaitlist[] = $child->id;
            }
            
            // Punkt 3: If waitlist is empty, auto-populate with all children from schedule
            if (empty($childrenOnWaitlist) && $selectedSchedule) {
                $scheduleChildren = $this->fetchTable('Children')->find()
                    ->where([
                        'schedule_id' => $scheduleId,
                        'is_active' => true
                    ])
                    ->orderBy(['organization_order' => 'ASC'])
                    ->all();
                
                $order = 1;
                foreach ($scheduleChildren as $child) {
                    $child->waitlist_order = $order++;
                    $this->fetchTable('Children')->save($child);
                    $childrenOnWaitlist[] = $child->id;
                }
                
                // Reload waitlist with newly populated children
                $waitlistChildren = $this->fetchTable('Children')->find()
                    ->where([
                        'schedule_id' => $scheduleId,
                        'waitlist_order IS NOT' => null
                    ])
                    ->orderBy(['waitlist_order' => 'ASC'])
                    ->all();
            }
        }
        
        // Get available children (not on waitlist)
        $availableChildren = [];
        if ($scheduleId && $selectedSchedule) {
            $availableChildrenQuery = $this->fetchTable('Children')->find()
                ->where([
                    'Children.organization_id' => $selectedSchedule->organization_id,
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
        
        $countNotOnWaitlist = count($availableChildren);
        
        // Build sibling groups map and load sibling names
        $siblingGroupsMap = [];
        $siblingNames = [];
        $missingSiblings = [];
        
        foreach ($waitlistChildren as $child) {
            if ($child->sibling_group_id) {
                $totalInGroup = $this->fetchTable('Children')->find()
                    ->where(['sibling_group_id' => $child->sibling_group_id])
                    ->count();
                
                if ($totalInGroup <= 1) {
                    continue; // Skip single-child groups
                }
                
                $siblingGroupsMap[$child->id] = $child->sibling_group_id;
                
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
                    
                    if (!in_array($sib->id, $childrenOnWaitlist)) {
                        $missingSiblings[] = [
                            'id' => $sib->id,
                            'name' => $sib->name,
                            'sibling_of' => $child->name,
                        ];
                    }
                }
                
                if (!empty($names)) {
                    $siblingNames[$child->id] = implode(', ', $names);
                }
            }
        }
        
        // Load sibling names for available children
        foreach ($availableChildren as $child) {
            if ($child->sibling_group_id && !isset($siblingNames[$child->id])) {
                $totalInGroup = $this->fetchTable('Children')->find()
                    ->where(['sibling_group_id' => $child->sibling_group_id])
                    ->count();
                
                if ($totalInGroup <= 1) {
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
                
                if (!empty($names)) {
                    $siblingNames[$child->id] = implode(', ', $names);
                }
            }
        }
        
        $this->set(compact('schedules', 'selectedSchedule', 'waitlistChildren', 'availableChildren', 'countNotOnWaitlist', 'siblingGroupsMap', 'siblingNames', 'missingSiblings', 'user'));
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
        
        $childrenTable = $this->fetchTable('Children');
        $child = $childrenTable->get($childId);
        
        // Check if already on waitlist
        if ($child->schedule_id == $scheduleId && $child->waitlist_order !== null) {
            $this->Flash->error(__('Child is already on waitlist.'));
            return $this->redirect(['action' => 'index', '?' => ['schedule_id' => $scheduleId]]);
        }
        
        // Find position: place after sibling or at end
        $nextOrder = null;
        if ($child->sibling_group_id) {
            // Find sibling on waitlist with highest order
            $siblingOnWaitlist = $childrenTable->find()
                ->where([
                    'schedule_id' => $scheduleId,
                    'sibling_group_id' => $child->sibling_group_id,
                    'id !=' => $childId,
                    'waitlist_order IS NOT' => null
                ])
                ->orderBy(['waitlist_order' => 'DESC'])
                ->first();
            
            if ($siblingOnWaitlist) {
                // Place directly after sibling
                $nextOrder = $siblingOnWaitlist->waitlist_order + 1;
                
                // Shift all following entries down by 1
                $childrenTable->updateAll(
                    ['waitlist_order' => new \Cake\Database\Expression\QueryExpression('waitlist_order + 1')],
                    [
                        'schedule_id' => $scheduleId,
                        'waitlist_order >=' => $nextOrder,
                        'waitlist_order IS NOT' => null
                    ]
                );
            }
        }
        
        // If no sibling found, add at end
        if ($nextOrder === null) {
            $maxOrder = $childrenTable->find()
                ->where([
                    'schedule_id' => $scheduleId,
                    'waitlist_order IS NOT' => null
                ])
                ->select(['max_order' => $childrenTable->find()->func()->max('waitlist_order')])
                ->first();
            
            $nextOrder = ($maxOrder && $maxOrder->max_order) ? $maxOrder->max_order + 1 : 1;
        }
        
        $child->schedule_id = $scheduleId;
        $child->waitlist_order = $nextOrder;
        
        if ($childrenTable->save($child)) {
            $this->Flash->success(__('Child added to waitlist.'));
        } else {
            $this->Flash->error(__('Could not add child to waitlist.'));
        }
        
        return $this->redirect(['action' => 'index', '?' => ['schedule_id' => $scheduleId]]);
    }

    /**
     * Remove child from waitlist
     *
     * @param string|null $id Child id
     * @return \Cake\Http\Response|null Redirects back to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        
        $childrenTable = $this->fetchTable('Children');
        $child = $childrenTable->get($id);
        $scheduleId = $child->schedule_id;
        
        $child->waitlist_order = null;
        // Keep schedule_id - child stays assigned
        
        if ($childrenTable->save($child)) {
            // Reorder remaining children
            $this->reorderWaitlist($scheduleId);
            $this->Flash->success(__('Child removed from waitlist.'));
        } else {
            $this->Flash->error(__('Could not remove child from waitlist.'));
        }
        
        return $this->redirect(['action' => 'index', '?' => ['schedule_id' => $scheduleId]]);
    }

    /**
     * Update waitlist order via AJAX (for drag & drop)
     *
     * @return \Cake\Http\Response JSON response
     */
    public function reorder()
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');
        
        $data = $this->request->getData();
        $scheduleId = $data['schedule_id'];
        $order = $data['order']; // Array of child IDs in new order
        
        $childrenTable = $this->fetchTable('Children');
        
        // Update waitlist_order based on new order
        foreach ($order as $index => $childId) {
            $child = $childrenTable->get($childId);
            $child->waitlist_order = $index + 1;
            $childrenTable->save($child);
        }
        
        $this->set([
            'success' => true,
            'message' => __('Waitlist order updated.'),
            '_serialize' => ['success', 'message']
        ]);
        
        return $this->response->withType('application/json');
    }

    /**
     * Reorder waitlist after deletion to remove gaps
     *
     * @param int $scheduleId Schedule ID
     * @return void
     */
    private function reorderWaitlist(int $scheduleId): void
    {
        $childrenTable = $this->fetchTable('Children');
        $children = $childrenTable->find()
            ->where([
                'schedule_id' => $scheduleId,
                'waitlist_order IS NOT' => null
            ])
            ->orderBy(['waitlist_order' => 'ASC'])
            ->all();
        
        $order = 1;
        foreach ($children as $child) {
            $child->waitlist_order = $order++;
            $childrenTable->save($child);
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
        
        // Get schedule to find organization
        $schedule = $this->fetchTable('Schedules')->get($scheduleId);
        
        // Get all active children for this organization
        $allChildrenQuery = $childrenTable->find()
            ->where([
                'Children.is_active' => true,
                'Children.organization_id' => $schedule->organization_id
            ]);
        
        $allChildren = $allChildrenQuery->all();
        
        // Get existing waitlist children IDs
        $existingOnWaitlist = $childrenTable->find()
            ->where([
                'schedule_id' => $scheduleId,
                'waitlist_order IS NOT' => null
            ])
            ->all();
        
        $existingChildIds = [];
        foreach ($existingOnWaitlist as $child) {
            $existingChildIds[] = (int)$child->id;
        }
        
        // Find next order number
        $maxOrder = $childrenTable->find()
            ->where([
                'schedule_id' => $scheduleId,
                'waitlist_order IS NOT' => null
            ])
            ->select(['max_order' => $childrenTable->find()->func()->max('waitlist_order')])
            ->first();
        
        $nextOrder = ($maxOrder && $maxOrder->max_order) ? (int)$maxOrder->max_order + 1 : 1;
        $addedCount = 0;
        
        // Add children that aren't already on the waitlist
        foreach ($allChildren as $child) {
            $childId = (int)$child->id;
            
            if (!in_array($childId, $existingChildIds, true)) {
                $child->schedule_id = (int)$scheduleId;
                $child->waitlist_order = (int)$nextOrder;
                $nextOrder++;
                
                if ($childrenTable->save($child)) {
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

    /**
     * Sort waitlist by a specific field (Punkt 2)
     *
     * @return \Cake\Http\Response|null Redirects back to index
     */
    public function sortBy()
    {
        $this->request->allowMethod(['post']);
        
        $scheduleId = $this->request->getQuery('schedule_id');
        $field = $this->request->getQuery('field');
        
        if (!$scheduleId || !in_array($field, ['birthdate', 'postal_code'])) {
            $this->Flash->error(__('Invalid sort parameters.'));
            return $this->redirect(['action' => 'index']);
        }
        
        $childrenTable = $this->fetchTable('Children');
        
        // Get all children on waitlist for this schedule
        $waitlistChildren = $childrenTable->find()
            ->where([
                'schedule_id' => $scheduleId,
                'waitlist_order IS NOT' => null
            ])
            ->orderBy([$field => 'ASC'])
            ->all();
        
        // Reorder based on sort field
        $order = 1;
        foreach ($waitlistChildren as $child) {
            $child->waitlist_order = $order++;
            $childrenTable->save($child);
        }
        
        $fieldName = ($field === 'birthdate') ? __('birthdate') : __('postal code');
        $this->Flash->success(__('Waitlist sorted by {0}.', $fieldName));
        
        return $this->redirect(['action' => 'index', '?' => ['schedule_id' => $scheduleId]]);
    }
}
