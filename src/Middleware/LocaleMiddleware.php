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
        // Get session
        $session = $request->getAttribute('session');
        
        if ($session) {
            // Read language from session, default to 'de'
            $language = $session->read('Config.language') ?: 'de';
            
            // Map short locale to full locale (de -> de_DE)
            $localeMap = [
                'de' => 'de_DE',
                'en' => 'en_US',
            ];
            $fullLocale = $localeMap[$language] ?? $language;
            
            // Set I18n locale
            I18n::setLocale($fullLocale);
        } else {
            // Set default German for non-authenticated requests
            I18n::setLocale('de_DE');
        }
        
        return $handler->handle($request);
    }
}
