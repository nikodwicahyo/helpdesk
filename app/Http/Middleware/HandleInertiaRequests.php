<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        // Disable version checking in local development to prevent 409 loops during hot reload
        if (app()->isLocal()) {
            return null;
        }
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'systemSettings' => function () {
                // Share essential system settings with all Inertia pages
                return [
                    'system_name' => \App\Models\SystemSetting::get('system_name', config('app.name')),
                    'system_email' => \App\Models\SystemSetting::get('system_email', config('mail.from.address')),
                    'timezone' => \App\Models\SystemSetting::get('timezone', config('app.timezone')),
                    'default_language' => \App\Models\SystemSetting::get('default_language', 'id'),
                    'items_per_page' => \App\Models\SystemSetting::get('items_per_page', 15),
                ];
            },
            'auth' => function () use ($request) {
                // Use web guard as primary source
                $user = auth('web')->user();
                
                // If not authenticated via web guard, check session fallback (legacy support)
                if (!$user && session('user_session')) {
                    $sessionData = session('user_session');
                    if (isset($sessionData['nip'])) {
                        // Attempt to retrieve user from service container if available
                        try {
                            $user = app(\App\Services\UserRetrievalService::class)->getUserByNip($sessionData['nip']);
                        } catch (\Exception $e) {
                            // Ignore error, user stays null
                        }
                    }
                }

                return [
                    'user' => $user ? [
                        'id' => $user->getKey(),
                        'nip' => $user->nip,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role ?? app(\App\Services\AuthService::class)->getUserRole($user),
                        'profile_photo_url' => method_exists($user, 'profile_photo_url') ? $user->profile_photo_url : null,
                    ] : null,
                    'role' => $user ? ($user->role ?? app(\App\Services\AuthService::class)->getUserRole($user)) : null,
                    'permissions' => $user ? app(\App\Services\AuthService::class)->getUserPermissions($user) : [],
                    'session' => $user ? [
                        'id' => session()->getId(),
                        'expires_at' => app(\App\Services\AuthService::class)->getSessionExpiryTime(),
                        'warning_at' => app(\App\Services\AuthService::class)->getSessionWarningTime(),
                        'minutes_remaining' => app(\App\Services\AuthService::class)->getSessionTimeRemaining(),
                    ] : null,
                ];
            },
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                    'warning' => $request->session()->get('warning'),
                    'info' => $request->session()->get('info'),
                ];
            },
        ]);
    }
}
