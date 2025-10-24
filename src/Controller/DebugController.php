<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\EmailDebugService;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;

/**
 * Debug Controller
 * 
 * Shows debug information for local development
 * Only accessible on localhost
 */
class DebugController extends AppController
{
    /**
     * Allow on localhost OR for authenticated admin users
     */
    public function initialize(): void
    {
        parent::initialize();
        
        // Skip auth check - we handle it manually in beforeFilter
    }
    
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Check if localhost
        if ($this->isLocalhost()) {
            // Localhost: Allow unauthenticated access
            $this->Authentication->addUnauthenticatedActions(['emails', 'clearEmails']);
            return $event;
        }
        
        // Production: Require admin authentication
        $user = $this->Authentication->getIdentity();
        if (!$user || $user->role !== 'admin') {
            $this->Flash->error(__('Debug routes are only available for administrators.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
    }
    
    /**
     * Show all debug emails
     */
    public function emails()
    {
        $emails = EmailDebugService::getEmails();
        $this->set(compact('emails'));
    }
    
    /**
     * Clear all debug emails
     */
    public function clearEmails()
    {
        EmailDebugService::clearEmails();
        $this->Flash->success(__('Debug emails cleared'));
        return $this->redirect(['action' => 'emails']);
    }
    
    /**
     * Check if we're on localhost
     *
     * @return bool
     */
    private function isLocalhost(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        return in_array($host, [
            'localhost',
            'localhost:8080',
            '127.0.0.1',
            '127.0.0.1:8080',
        ]) || strpos($host, 'localhost') !== false;
    }
}
