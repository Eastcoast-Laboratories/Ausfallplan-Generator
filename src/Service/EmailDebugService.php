<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Session;

/**
 * Email Debug Service
 * 
 * For local development: Instead of sending emails, stores them in session
 * so they can be displayed on a debug page for testing
 */
class EmailDebugService
{
    /**
     * Send or store email based on environment
     *
     * @param array $email Email data (to, subject, body, links)
     * @return bool Success
     */
    public static function send(array $email): bool
    {
        // Check if we're in localhost/development
        $isLocalhost = self::isLocalhost();
        
        if ($isLocalhost) {
            // Store in session for debug display
            self::storeEmail($email);
            return true;
        } else {
            // TODO: Real email sending via SMTP
            // For now, just log it
            return self::logEmail($email);
        }
    }
    
    /**
     * Check if we're on localhost
     *
     * @return bool
     */
    private static function isLocalhost(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        return in_array($host, [
            'localhost',
            'localhost:8080',
            '127.0.0.1',
            '127.0.0.1:8080',
        ]) || strpos($host, 'localhost') !== false;
    }
    
    /**
     * Store email in session for debug display
     *
     * @param array $email Email data
     * @return void
     */
    private static function storeEmail(array $email): void
    {
        $session = new Session();
        $emails = $session->read('Debug.emails') ?? [];
        
        $email['timestamp'] = new \DateTime();
        $emails[] = $email;
        
        // Keep only last 20 emails
        if (count($emails) > 20) {
            $emails = array_slice($emails, -20);
        }
        
        $session->write('Debug.emails', $emails);
    }
    
    /**
     * Get all stored debug emails
     *
     * @return array
     */
    public static function getEmails(): array
    {
        $session = new Session();
        return $session->read('Debug.emails') ?? [];
    }
    
    /**
     * Clear all debug emails
     *
     * @return void
     */
    public static function clearEmails(): void
    {
        $session = new Session();
        $session->delete('Debug.emails');
    }
    
    /**
     * Log email (fallback for production)
     *
     * @param array $email Email data
     * @return bool
     */
    private static function logEmail(array $email): bool
    {
        $logMessage = sprintf(
            "Email to %s: %s\n%s",
            $email['to'] ?? 'unknown',
            $email['subject'] ?? 'no subject',
            $email['body'] ?? ''
        );
        
        error_log($logMessage);
        return true;
    }
}
