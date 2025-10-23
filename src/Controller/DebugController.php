<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\EmailDebugService;

/**
 * Debug Controller
 * 
 * Shows debug information for local development
 * Only accessible on localhost
 */
class DebugController extends AppController
{
    /**
     * Only allow on localhost
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Allow unauthenticated access on localhost only
        if ($this->isLocalhost()) {
            $this->Authentication->addUnauthenticatedActions(['emails', 'clearEmails']);
        } else {
            // Not localhost - deny access
            $this->Flash->error('Debug functions only available on localhost');
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
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
