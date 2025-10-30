<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function Cake\I18n\__;

/**
 * Authorization Middleware
 * 
 * Enforces role-based access control:
 * - viewer: Read-only access to own organization
 * - editor: Can edit own organization data
 * - admin: Can access and edit everything
 */
class AuthorizationMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        /** @var ServerRequest $request */
        $identity = $request->getAttribute('identity');
        
        // Skip authorization for public routes
        if (!$identity) {
            return $handler->handle($request);
        }
        
        $controller = $request->getParam('controller');
        $action = $request->getParam('action');
        
        // Always allow these safe actions for all authenticated users
        $alwaysAllowed = ['logout', 'login', 'register', 'display'];
        if (in_array($action, $alwaysAllowed)) {
            return $handler->handle($request);
        }
        
        // System admin can do everything
        if ($identity->is_system_admin ?? false) {
            return $handler->handle($request);
        }
        
        // Get user's role from organization_users table
        $role = $this->getUserRole($identity);
        
        // org_admin has full access (like old admin role)
        if ($role === 'org_admin') {
            return $handler->handle($request);
        }
        
        // Viewer: Only read actions allowed
        if ($role === 'viewer') {
            $allowedActions = ['index', 'view', 'generateReport'];
            
            if (!in_array($action, $allowedActions)) {
                // Redirect with flash message (info, not error)
                $session = $request->getAttribute('session');
                $session->write('Flash.flash', [
                    [
                        'message' => __('You do not have permission to perform actions. (Viewer role is read-only)'),
                        'key' => 'flash',
                        'element' => 'Flash/info',
                        'params' => ['class' => 'info']
                    ]
                ]);
                
                $response = new Response();
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/');
            }
        }
        
        // Editor: Can edit own organization data
        if ($role === 'editor') {
            // Editor cannot access admin user management
            if ($controller === 'Users' && in_array($action, ['index', 'add', 'edit', 'delete', 'approve'])) {
                // Redirect with flash message
                $session = $request->getAttribute('session');
                $session->write('Flash.flash', [
                    [
                        'message' => 'Editor können keine Benutzer verwalten.',
                        'key' => 'flash',
                        'element' => 'Flash/error',
                        'params' => ['class' => 'error']
                    ]
                ]);
                
                $response = new Response();
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/');
            }
        }
        
        return $handler->handle($request);
    }

    /**
     * Get user's highest role from organization_users
     *
     * @param object $identity User identity
     * @return string Role (org_admin, editor, viewer)
     */
    private function getUserRole($identity): string
    {
        // Get table using TableRegistry
        $orgUsersTable = \Cake\ORM\TableRegistry::getTableLocator()->get('OrganizationUsers');
        
        // Get all organization memberships
        $orgUsers = $orgUsersTable->find()
            ->where(['user_id' => $identity->id])
            ->all();
        
        // No organization membership = viewer (most restrictive)
        if ($orgUsers->isEmpty()) {
            return 'viewer';
        }
        
        // Check for highest role: org_admin > editor > viewer
        foreach ($orgUsers as $orgUser) {
            if ($orgUser->role === 'org_admin') {
                return 'org_admin';
            }
        }
        
        foreach ($orgUsers as $orgUser) {
            if ($orgUser->role === 'editor') {
                return 'editor';
            }
        }
        
        return 'viewer';
    }
}
