<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        
        // Get user's role from organization_users (this is set by getPrimaryOrganization in controllers)
        // For now, use old role field as fallback
        $role = $identity->role ?? 'viewer';
        
        // Legacy admin check
        if ($role === 'admin') {
            return $handler->handle($request);
        }
        
        // Viewer: Only read actions allowed
        if ($role === 'viewer') {
            $allowedActions = ['index', 'view'];
            
            if (!in_array($action, $allowedActions)) {
                // Redirect with flash message
                $session = $request->getAttribute('session');
                $session->write('Flash.flash', [
                    [
                        'message' => 'You do not have permission to perform this action. (Viewer role is read-only)',
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
        
        // Editor: Can edit own organization data
        if ($role === 'editor') {
            // Editor cannot access admin user management
            if ($controller === 'Users' && in_array($action, ['index', 'add', 'edit', 'delete', 'approve'])) {
                // Redirect with flash message
                $session = $request->getAttribute('session');
                $session->write('Flash.flash', [
                    [
                        'message' => 'Editors cannot manage users.',
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
}
