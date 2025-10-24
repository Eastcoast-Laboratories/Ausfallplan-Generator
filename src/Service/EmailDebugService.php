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
     * Get email debug log file path
     *
     * @return string
     */
    private static function getEmailLogFile(): string
    {
        return TMP . 'debug_emails.json';
    }
    
    /**
     * Store email in file for debug display (works in both CLI and web context)
     *
     * @param array $email Email data
     * @return void
     */
    private static function storeEmail(array $email): void
    {
        $logFile = self::getEmailLogFile();
        
        // Read existing emails
        $emails = [];
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if ($content) {
                $emails = json_decode($content, true) ?? [];
            }
        }
        
        // Add timestamp
        $email['timestamp'] = date('Y-m-d H:i:s');
        $emails[] = $email;
        
        // Keep only last 50 emails
        if (count($emails) > 50) {
            $emails = array_slice($emails, -50);
        }
        
        // Write back to file
        file_put_contents($logFile, json_encode($emails, JSON_PRETTY_PRINT));
        chmod($logFile, 0666); // Make sure it's writable
    }
    
    /**
     * Get all stored debug emails
     *
     * @return array
     */
    public static function getEmails(): array
    {
        $logFile = self::getEmailLogFile();
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $content = file_get_contents($logFile);
        if (!$content) {
            return [];
        }
        
        return json_decode($content, true) ?? [];
    }
    
    /**
     * Clear all debug emails
     *
     * @return void
     */
    public static function clearEmails(): void
    {
        $logFile = self::getEmailLogFile();
        if (file_exists($logFile)) {
            unlink($logFile);
        }
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
                // Send HTML email with deliver()
                $mailer->deliver($htmlBody);
            } else {
                $mailer->setEmailFormat('text');
                // Send plain text email with deliver()
                $mailer->deliver($email['body'] ?? '');
            }
            
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
