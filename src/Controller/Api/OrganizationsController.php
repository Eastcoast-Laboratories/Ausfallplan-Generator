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
        // RequestHandler is loaded automatically in AppController
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
        
        // Minimum 2 characters required
        if (strlen($query) >= 2) {
            $organizationsTable = $this->fetchTable('Organizations');
            $results = $organizationsTable->find()
                ->where([
                    'name !=' => 'keine organisation',
                    'name LIKE' => '%' . $query . '%'
                ])
                ->select(['id', 'name'])
                ->orderBy(['name' => 'ASC'])
                ->limit(50)
                ->all();
            
            foreach ($results as $org) {
                $organizations[] = [
                    'id' => $org->id,
                    'name' => $org->name
                ];
            }
        }
        
        // Return JSON directly
        $this->response = $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['organizations' => $organizations]));
        
        return $this->response;
    }
}
