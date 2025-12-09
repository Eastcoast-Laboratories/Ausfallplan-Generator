#!/usr/bin/env php
<?php
/**
 * Test User Registration - Simulates a user registration to check email flow
 */

// Bootstrap CakePHP
require dirname(__DIR__) . '/config/bootstrap.php';

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\ConsoleInput;
use Cake\Datasource\FactoryLocator;

$io = new ConsoleIo(new ConsoleOutput(), new ConsoleOutput(), new ConsoleInput());

$io->out('<info>Testing User Registration Email Flow</info>');
$io->out('');

// Get tables
$usersTable = FactoryLocator::get('Table')->get('Users');
$organizationsTable = FactoryLocator::get('Table')->get('Organizations');
$orgUsersTable = FactoryLocator::get('Table')->get('OrganizationUsers');

// Create test data
$timestamp = time();
$testEmail = "testuser{$timestamp}@test.com";
$testOrgName = "Test Org {$timestamp}";

$io->out("Creating user: {$testEmail}");
$io->out("Creating organization: {$testOrgName}");
$io->out('');

// 1. Create organization
$organization = $organizationsTable->newEntity([
    'name' => $testOrgName,
    'contact_email' => $testEmail
]);

if (!$organizationsTable->save($organization)) {
    $io->error('Failed to create organization');
    exit(1);
}

$io->success("✓ Organization created: ID {$organization->id}");

// 2. Create user
$user = $usersTable->newEntity([
    'email' => $testEmail,
    'password' => 'testpass123',
    'status' => 'pending',
    'email_verified' => false,
    'email_token' => bin2hex(random_bytes(16))
]);

if (!$usersTable->save($user)) {
    $io->error('Failed to create user');
    exit(1);
}

$io->success("✓ User created: ID {$user->id}");

// 3. Create organization_users entry
$orgUser = $orgUsersTable->newEntity([
    'organization_id' => $organization->id,
    'user_id' => $user->id,
    'role' => 'org_admin',
    'is_primary' => true,
    'joined_at' => new \DateTime(),
]);

if (!$orgUsersTable->save($orgUser)) {
    $io->error('Failed to create org_user relation');
    exit(1);
}

$io->success("✓ Organization-User relation created");
$io->out('');

// 4. Send verification email (like in register())
$io->out('<info>Sending verification email...</info>');

$verifyUrl = \Cake\Routing\Router::url([
    'controller' => 'Users',
    'action' => 'verify',
    $user->email_token
], true);

\App\Service\EmailDebugService::send([
    'to' => $user->email,
    'subject' => 'Verify your email address',
    'body' => "Hello,\n\nPlease verify your email address by clicking the link below:\n\n{$verifyUrl}\n\nIf you did not register, please ignore this email.",
    'links' => [
        'Verify Email' => $verifyUrl
    ],
    'data' => [
        'user_id' => $user->id,
        'email' => $user->email,
        'token' => $user->email_token
    ]
]);

$io->success('✓ Verification email sent (stored in debug)');

// 5. Send sysadmin notification (simulating the controller method)
$io->out('<info>Sending sysadmin notification...</info>');

$sysadminEmail = \Cake\Core\env('SYSADMIN_BCC_EMAIL', 'sysadmin@fairnestplan.z11.de');

$io->out("Sysadmin email from env: {$sysadminEmail}");

if (empty($sysadminEmail)) {
    $io->warning('⚠ Sysadmin email is empty, skipping notification');
} else {
    $adminUrl = \Cake\Routing\Router::url([
        'controller' => 'Admin/Users',
        'action' => 'index'
    ], true);
    
    \App\Service\EmailDebugService::send([
        'to' => $sysadminEmail,
        'subject' => "🔔 New User Registration: {$user->email}",
        'body' => "A new user has registered on FairNestPlan.\n\n" .
                  "User Details:\n" .
                  "- Email: {$user->email}\n" .
                  "- User ID: {$user->id}\n" .
                  "- Status: {$user->status}\n\n" .
                  "Organization:\n" .
                  "- Name: {$organization->name}\n" .
                  "- Organization ID: {$organization->id}\n" .
                  "- Type: NEW organization\n\n" .
                  "Role: Organization Admin\n\n" .
                  "Manage users: {$adminUrl}",
        'links' => [
            'Manage Users' => $adminUrl
        ],
        'data' => [
            'event' => 'user_registration',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
            'role' => 'org_admin',
            'is_new_organization' => true
        ]
    ]);
    
    $io->success('✓ Sysadmin notification sent (stored in debug)');
}

$io->out('');
$io->out('<info>Summary:</info>');
$io->out("User: {$testEmail}");
$io->out("Organization: {$testOrgName}");
$io->out('');
$io->out('Check debug emails at: http://localhost:8080/debug/emails');
$io->out('');
$io->success('Test completed!');
