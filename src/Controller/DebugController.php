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
     * Only allow on localhost
     */
    public function initialize(): void
    {
        parent::initialize();
        
        // Allow in development OR if explicitly enabled via config
        if (!Configure::read('debug') && !Configure::read('allowDebugRoutes')) {
            throw new NotFoundException('Debug controller only available in development mode');
        }
    }
    
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        
        // Allow unauthenticated access on localhost OR if config is set
        $allowDebug = $this->isLocalhost() || Configure::read('allowDebugRoutes');
        
        if ($allowDebug) {
            $this->Authentication->addUnauthenticatedActions(['emails', 'clearEmails']);
        } else {
            $this->Flash->error(__('Debug routes are only available on localhost.'));
            return $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
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
