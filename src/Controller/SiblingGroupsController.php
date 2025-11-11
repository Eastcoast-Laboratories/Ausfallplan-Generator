<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * SiblingGroups Controller
 *
 * @property \App\Model\Table\SiblingGroupsTable $SiblingGroups
 */
class SiblingGroupsController extends AppController
{
    /**
     * Index method - List all sibling groups
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
        
        // If no selection and user has only one org, use it
        if (!$selectedOrgId && count($userOrgs) === 1) {
            $selectedOrgId = $userOrgs[0]->id;
        }
        
        // System admin sees all sibling groups (with optional filter)
        if ($user && $user->is_system_admin) {
            $query = $this->SiblingGroups->find()
                ->contain(['Organizations', 'Children'])
                ->orderBy(['SiblingGroups.label' => 'ASC']);
            
            // Filter by organization if selected
            if ($selectedOrgId) {
                $query->where(['SiblingGroups.organization_id' => $selectedOrgId]);
            }
            
            $siblingGroups = $query->all();
        } else {
            // Regular users see sibling groups from their organization(s)
            $orgIds = array_map(fn($org) => $org->id, $userOrgs);
            
            if (empty($orgIds)) {
                $this->Flash->info(__('Sie sind noch keiner Organisation zugeordnet. Bitte erstellen Sie eine Organisation.'));
                return $this->redirect(['controller' => 'Admin/Organizations', 'action' => 'index']);
            }
            
            $query = $this->SiblingGroups->find()
                ->contain(['Organizations', 'Children'])
                ->orderBy(['SiblingGroups.label' => 'ASC']);
            
            // Filter by selected org or all user's orgs
            if ($selectedOrgId && in_array($selectedOrgId, $orgIds)) {
                $query->where(['SiblingGroups.organization_id' => $selectedOrgId]);
            } else {
                $query->where(['SiblingGroups.organization_id IN' => $orgIds]);
            }
            
            $siblingGroups = $query->all();
        }
        
        // Mark groups with only 1 child as errors
        $errorGroups = [];
        foreach ($siblingGroups as $group) {
            if (count($group->children) <= 1) {
                $errorGroups[] = $group->id;
            }
        }

        $this->set(compact('siblingGroups', 'errorGroups', 'userOrgs', 'hasMultipleOrgs', 'selectedOrgId'));
    }

    /**
     * View method - Display a single sibling group
     *
     * @param string|null $id Sibling Group id.
     * @return \Cake\Http\Response|null|void
     */
    public function view($id = null)
    {
        $siblingGroup = $this->SiblingGroups->get($id, contain: [
            'Organizations',
            'Children',
        ]);
        
        // CRITICAL: Check if this is an invalid group (only 1 child)
        $isErrorGroup = count($siblingGroup->children) <= 1;
        if ($isErrorGroup) {
            $this->Flash->warning(__('⚠️ WARNUNG: Diese Geschwistergruppe hat nur ein Kind! Eine Geschwistergruppe muss mindestens 2 Kinder enthalten. Bitte fügen Sie ein weiteres Kind hinzu oder löschen Sie die Gruppe.'));
        }

        $this->set(compact('siblingGroup', 'isErrorGroup'));
    }

    /**
     * Add method - Create a new sibling group
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add()
    {
        $siblingGroup = $this->SiblingGroups->newEmptyEntity();
        
        // Get user's organizations
        $user = $this->Authentication->getIdentity();
        $userOrgs = $this->getUserOrganizations();
        
        // Get selected organization from query or session
        $selectedOrgId = $this->request->getQuery('organization_id');
        
        // Handle "all" as no filter
        if ($selectedOrgId === 'all' || $selectedOrgId === '') {
            $selectedOrgId = null;
            $this->request->getSession()->delete('selectedOrgId');
        } elseif ($selectedOrgId) {
            $this->request->getSession()->write('selectedOrgId', $selectedOrgId);
        } else {
            $selectedOrgId = $this->request->getSession()->read('selectedOrgId');
        }
        
        // Default to first org if still no selection
        if (!$selectedOrgId && !empty($userOrgs)) {
            $selectedOrgId = $userOrgs[0]->id;
        }
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Set organization from form or selected org
            if (empty($data['organization_id'])) {
                if ($selectedOrgId) {
                    $data['organization_id'] = $selectedOrgId;
                } elseif (!empty($userOrgs)) {
                    $data['organization_id'] = $userOrgs[0]->id;
                } else {
                    $this->Flash->error(__('You must be assigned to an organization to create sibling groups.'));
                    $this->set(compact('siblingGroup', 'userOrgs', 'selectedOrgId'));
                    return;
                }
            }
            
            $siblingGroup = $this->SiblingGroups->patchEntity($siblingGroup, $data);
            
            if ($this->SiblingGroups->save($siblingGroup)) {
                $this->Flash->success(__('The sibling group has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The sibling group could not be saved. Please try again.'));
        }
        
        $this->set(compact('siblingGroup', 'userOrgs', 'selectedOrgId'));
    }

    /**
     * Edit method - Update an existing sibling group
     *
     * @param string|null $id Sibling Group id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     */
    public function edit($id = null)
    {
        $siblingGroup = $this->SiblingGroups->get($id, contain: ['Children']);
        
        // Get user's organizations
        $user = $this->Authentication->getIdentity();
        $userOrgs = $this->getUserOrganizations();
        
        // Get selected organization (current group's org or from query)
        $selectedOrgId = $this->request->getQuery('organization_id') ?: $siblingGroup->organization_id;
        
        // Check if group has children (if yes, organization cannot be changed)
        $hasChildren = !empty($siblingGroup->children) && count($siblingGroup->children) > 0;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $siblingGroup = $this->SiblingGroups->patchEntity($siblingGroup, $this->request->getData());
            
            if ($this->SiblingGroups->save($siblingGroup)) {
                $this->Flash->success(__('The sibling group has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The sibling group could not be updated. Please try again.'));
        }
        
        $this->set(compact('siblingGroup', 'userOrgs', 'selectedOrgId', 'hasChildren'));
    }

    /**
     * Delete method - Remove a sibling group
     *
     * @param string|null $id Sibling Group id.
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $siblingGroup = $this->SiblingGroups->get($id, contain: ['Children']);

        // Check if group has children
        if (count($siblingGroup->children) > 0) {
            $this->Flash->error(__('Cannot delete sibling group with children. Please remove children from this group first.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($this->SiblingGroups->delete($siblingGroup)) {
            $this->Flash->success(__('The sibling group has been deleted.'));
        } else {
            $this->Flash->error(__('The sibling group could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Remove child from sibling group - Only removes the connection, not the child
     *
     * @param string|null $groupId Sibling Group id.
     * @param string|null $childId Child id.
     * @return \Cake\Http\Response|null Redirects to group view
     */
    public function removeChild($groupId = null, $childId = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        
        $childrenTable = $this->fetchTable('Children');
        $child = $childrenTable->get($childId);
        
        // Remove sibling group connection by setting sibling_group_id to null
        $child->sibling_group_id = null;
        
        if ($childrenTable->save($child)) {
            $this->Flash->success(__('Kind wurde aus der Geschwistergruppe entfernt.'));
        } else {
            $this->Flash->error(__('Kind konnte nicht aus der Geschwistergruppe entfernt werden.'));
        }

        return $this->redirect(['action' => 'view', $groupId]);
    }
}
