<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\RoleRouteService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\HttpFoundation\Response;

class GuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use AuthService::getCurrentAuthenticatedUser() as single source
        $user = app(AuthService::class)->getCurrentAuthenticatedUser();

        if ($user) {
            // DO NOT check timeout here - SessionTimeoutMiddleware is the single authority for timeout enforcement
            // This middleware only handles role-based redirects for already authenticated users

            // Redirect based on user role
            return $this->getRoleBasedRedirect($user);
        }

        return $next($request);
    }

    /**
     * Get role-based redirect URL for authenticated users
     */
    private function getRoleBasedRedirect($user): Response
    {
        $role = app(AuthService::class)->getUserRole($user);

        // Use centralized RoleRouteService for role-based routing
        return RoleRouteService::redirectToDashboard($role);
    }
}