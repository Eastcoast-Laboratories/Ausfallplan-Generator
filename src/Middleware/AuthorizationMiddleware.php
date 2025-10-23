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
        
        $role = $identity->role ?? 'viewer';
        $controller = $request->getParam('controller');
        $action = $request->getParam('action');
        
        // Admin can do everything
        if ($role === 'admin') {
            return $handler->handle($request);
        }
        
        // Viewer: Only read actions allowed
        if ($role === 'viewer') {
            $allowedActions = ['index', 'view', 'display'];
            
            if (!in_array($action, $allowedActions)) {
                $response = new Response();
                return $response
                    ->withStatus(403)
                    ->withStringBody('You do not have permission to perform this action. (Viewer role is read-only)');
            }
        }
        
        // Editor: Can edit own organization data
        if ($role === 'editor') {
            // Editor cannot access admin user management
            if ($controller === 'Users' && in_array($action, ['index', 'add', 'edit', 'delete', 'approve'])) {
                $response = new Response();
                return $response
                    ->withStatus(403)
                    ->withStringBody('Editors cannot manage users.');
            }
        }
        
        return $handler->handle($request);
    }
}
