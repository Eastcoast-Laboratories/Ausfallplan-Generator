<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\I18n\I18n;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Locale Middleware
 * 
 * Sets the application locale based on session configuration
 */
class LocaleMiddleware implements MiddlewareInterface
{
    /**
     * Process method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Skip locale setting in test environment if already set
        if (PHP_SAPI === 'cli' && I18n::getLocale() !== 'en') {
            // In tests, respect the already-set locale (from setUp)
            return $handler->handle($request);
        }
        
        // Get session
        $session = $request->getAttribute('session');
        
        // Always set German as default first
        I18n::setLocale('de_DE');
        
        if ($session) {
            // Read language from session
            $language = $session->read('Config.language');
            
            if ($language) {
                // Map short locale to full locale (de -> de_DE)
                $localeMap = [
                    'de' => 'de_DE',
                    'en' => 'en_US',
                ];
                $fullLocale = $localeMap[$language] ?? $language;
                
                // Set I18n locale
                I18n::setLocale($fullLocale);
                
                // Debug logging (can be removed later)
                error_log("LocaleMiddleware: Set locale to {$fullLocale} (from session: {$language})");
            } else {
                error_log("LocaleMiddleware: No language in session, using default de_DE");
            }
        } else {
            error_log("LocaleMiddleware: No session attribute");
        }
        
        return $handler->handle($request);
    }
}
