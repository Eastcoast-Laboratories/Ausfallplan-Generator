<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

/**
 * API Organizations Controller
 * Provides JSON API for organization autocomplete
 */
class OrganizationsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        // Allow unauthenticated access for registration
        $this->Authentication->addUnauthenticatedActions(['search']);
    }

    /**
     * Search organizations for autocomplete
     *
     * @return void
     */
    public function search()
    {
        $this->request->allowMethod(['get']);
        $query = $this->request->getQuery('q', '');
        
        $organizations = [];
        
        if (strlen($query) >= 2) {
            $organizationsTable = $this->fetchTable('Organizations');
            $results = $organizationsTable->find()
                ->where([
                    'name LIKE' => '%' . $query . '%',
                    'name !=' => 'keine organisation'
                ])
                ->select(['id', 'name'])
                ->orderBy(['name' => 'ASC'])
                ->limit(10)
                ->all();
            
            foreach ($results as $org) {
                $organizations[] = [
                    'id' => $org->id,
                    'name' => $org->name
                ];
            }
        }
        
        $this->set([
            'organizations' => $organizations,
            '_serialize' => ['organizations']
        ]);
        
        $this->viewBuilder()->setOption('serialize', ['organizations']);
        $this->RequestHandler->renderAs($this, 'json');
    }
}
