<?php
/**
 * Test script for EmailDebugService BCC functionality
 */
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

use App\Service\EmailDebugService;

echo "Testing EmailDebugService with BCC\n";
echo "===================================\n\n";

$email = [
    'to' => 'test@example.com',
    'subject' => 'Test Email mit BCC',
    'body' => 'Dies ist eine Test-Mail zum Testen der BCC-Funktion.',
    'links' => ['Verification Link' => 'https://example.com/verify/abc123']
];

echo "Email Details:\n";
echo "  To: " . $email['to'] . "\n";
echo "  Subject: " . $email['subject'] . "\n";
echo "  BCC: sysadmin@fairnestplan.z11.de (automatisch)\n\n";

$result = EmailDebugService::send($email);

if ($result) {
    echo "✅ Email successfully processed!\n";
    
    // Check if localhost - if so, show debug emails
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') !== false) {
        echo "\nℹ️  Running on localhost - email stored in session for debug display\n";
        echo "   View at: http://localhost:8765/debug/emails\n";
    } else {
        echo "\nℹ️  Running on production - email sent via SMTP\n";
        echo "   BCC sent to: sysadmin@fairnestplan.z11.de\n";
    }
} else {
    echo "❌ Email sending failed!\n";
}

echo "\n";
