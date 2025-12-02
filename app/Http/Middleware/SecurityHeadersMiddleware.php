<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Apply security headers
        $this->applySecurityHeaders($response, $request);

        return $response;
    }

    /**
     * Apply security headers to response
     */
    private function applySecurityHeaders(Response $response, Request $request): void
    {
        $config = config('middleware.security_headers');

        // X-Frame-Options: Prevent clickjacking attacks
        if ($config['x_frame_options'] && !$response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', $config['x_frame_options']);
        }

        // X-Content-Type-Options: Prevent MIME type sniffing
        if ($config['x_content_type_options'] && !$response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', $config['x_content_type_options']);
        }

        // X-XSS-Protection: Enable XSS filtering
        if ($config['x_xss_protection'] && !$response->headers->has('X-XSS-Protection')) {
            $response->headers->set('X-XSS-Protection', $config['x_xss_protection']);
        }

        // Strict-Transport-Security: Enforce HTTPS
        if ($config['strict_transport_security'] && $this->isSecureRequest($request)) {
            $response->headers->set('Strict-Transport-Security', $config['strict_transport_security']);
        }

        // Referrer-Policy: Control referrer information
        if ($config['referrer_policy'] && !$response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', $config['referrer_policy']);
        }

        // Content-Security-Policy: Control resource loading
        if ($config['content_security_policy'] && !$response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $config['content_security_policy']);
        }

        // Additional security headers for API responses
        if ($request->is('api/*')) {
            $response->headers->set('X-API-Protected', 'true');
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        // Security headers for HTML responses
        if ($response->headers->has('Content-Type') &&
            str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('X-Protected-By', 'HelpDesk-Kemlu-Security');
        }
    }

    /**
     * Check if request is secure (HTTPS)
     */
    private function isSecureRequest(Request $request): bool
    {
        return $request->isSecure() ||
               $request->header('X-Forwarded-Proto') === 'https' ||
               $request->header('X-Forwarded-Ssl') === 'on';
    }

    /**
     * Get CSP directives based on request type
     */
    private function getCSPDirectives(Request $request): string
    {
        $baseCSP = "default-src 'self'";

        // Allow Inertia/Vue.js for SPA
        if ($request->header('X-Inertia')) {
            $baseCSP .= "; script-src 'self' 'unsafe-inline' 'unsafe-eval'";
            $baseCSP .= "; style-src 'self' 'unsafe-inline'";
            $baseCSP .= "; connect-src 'self'";
        }

        // Allow API calls
        if ($request->is('api/*')) {
            $baseCSP .= "; connect-src 'self'";
        }

        return $baseCSP;
    }
}