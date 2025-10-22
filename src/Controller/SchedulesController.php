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
        // Get current user's organization
        $user = $this->Authentication->getIdentity();
        
        $schedules = $this->Schedules->find()
            ->where(['Schedules.organization_id' => $user->organization_id])
            ->contain(['Organizations'])
            ->orderBy(['Schedules.created' => 'DESC'])
            ->all();

        $this->set(compact('schedules'));
    }

    /**
     * View method - Display a single schedule
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void
     */
    public function view($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: ['Organizations', 'ScheduleDays']);

        $this->set(compact('schedule'));
    }

    /**
     * Add method - Create a new schedule
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add()
    {
        $schedule = $this->Schedules->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            
            // Set organization from authenticated user
            $user = $this->Authentication->getIdentity();
            $data['organization_id'] = $user->organization_id;
            
            // Set default state to 'draft'
            if (empty($data['state'])) {
                $data['state'] = 'draft';
            }
            
            $schedule = $this->Schedules->patchEntity($schedule, $data);
            
            if ($this->Schedules->save($schedule)) {
                $this->Flash->success(__('The schedule has been created.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The schedule could not be saved. Please try again.'));
        }
        
        $this->set(compact('schedule'));
    }

    /**
     * Edit method - Update an existing schedule
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     */
    public function edit($id = null)
    {
        $schedule = $this->Schedules->get($id, contain: []);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $schedule = $this->Schedules->patchEntity($schedule, $this->request->getData());
            
            if ($this->Schedules->save($schedule)) {
                $this->Flash->success(__('The schedule has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            
            $this->Flash->error(__('The schedule could not be updated. Please try again.'));
        }
        
        $this->set(compact('schedule'));
    }

    /**
     * Delete method - Remove a schedule
     *
     * @param string|null $id Schedule id.
     * @return \Cake\Http\Response|null Redirects to index
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $schedule = $this->Schedules->get($id);

        if ($this->Schedules->delete($schedule)) {
            $this->Flash->success(__('The schedule has been deleted.'));
        } else {
            $this->Flash->error(__('The schedule could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
