<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Session;
use Cake\Mailer\Mailer;
use function Cake\Core\env;

/**
 * Email Debug Service
 * 
 * For local development: Instead of sending emails, stores them in session
 * so they can be displayed on a debug page for testing
 */
class EmailDebugService
{
    /**
     * Get system admin email for BCC (configurable via environment)
     * Set to empty string to disable BCC
     *
     * @return string|null
     */
    private static function getSysadminEmail(): ?string
    {
        $email = env('SYSADMIN_BCC_EMAIL', 'ausfallplan-sysadmin@it.z11.de');
        return !empty($email) ? $email : null;
    }
    
    /**
     * Send or store email based on environment
     *
     * @param array $email Email data (to, subject, body, links)
     * @return bool Success
     */
    public static function send(array $email): bool
    {
        // ALWAYS store in session for debug display (both localhost and production)
        self::storeEmail($email);
        
        // Check if we're in localhost/development
        $isLocalhost = self::isLocalhost();
        
        if ($isLocalhost) {
            // On localhost: Only send if configured
            if (Configure::read('Email.alsoSendOnLocalhost')) {
                return self::sendRealEmail($email);
            }
            return true;
        } else {
            // On production: Try to send real email, fallback to log
            return self::sendRealEmail($email);
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
     * Send real email via CakePHP Mailer
     *
     * @param array $email Email data
     * @return bool
     */
    private static function sendRealEmail(array $email): bool
    {
        try {
            $mailer = new Mailer('default');
            
            // Set sender
            $mailer->setFrom(['noreply@ausfallplan-generator.z11.de' => 'Ausfallplan Generator']);
            
            // Set recipient
            $mailer->setTo($email['to']);
            
            // Add BCC to sysadmin if configured
            $sysadminEmail = self::getSysadminEmail();
            if ($sysadminEmail) {
                $mailer->setBcc($sysadminEmail);
            }
            
            // Set subject and body
            $mailer->setSubject($email['subject'] ?? 'No Subject');
            
            // Use HTML if links are provided, otherwise plain text
            if (!empty($email['links'])) {
                $htmlBody = nl2br(htmlspecialchars($email['body'] ?? ''));
                $htmlBody .= '<br><br>';
                foreach ($email['links'] as $label => $url) {
                    $htmlBody .= sprintf('<a href="%s">%s</a><br>', htmlspecialchars($url), htmlspecialchars($label));
                }
                $mailer->setEmailFormat('html');
                $mailer->setBodyText($email['body'] ?? ''); // Plain text fallback
                $mailer->setBodyHtml($htmlBody);
            } else {
                $mailer->setEmailFormat('text');
                $mailer->setBodyText($email['body'] ?? '');
            }
            
            // Send the email
            $mailer->send();
            
            return true;
        } catch (\Exception $e) {
            // Log error and fallback
            error_log('Email sending failed: ' . $e->getMessage());
            return self::logEmail($email);
        }
    }
    
    /**
     * Log email (fallback for production)
     *
     * @param array $email Email data
     * @return bool
     */
    private static function logEmail(array $email): bool
    {
        $sysadminEmail = self::getSysadminEmail();
        $bccInfo = $sysadminEmail ? " (BCC: {$sysadminEmail})" : ' (No BCC)';
        
        $logMessage = sprintf(
            "Email to %s%s: %s\n%s",
            $email['to'] ?? 'unknown',
            $bccInfo,
            $email['subject'] ?? 'no subject',
            $email['body'] ?? ''
        );
        
        error_log($logMessage);
        return true;
    }
}
