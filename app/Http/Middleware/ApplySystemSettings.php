<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;

class ApplySystemSettings
{
    /**
     * Handle an incoming request.
     * Apply system settings to the application at runtime.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apply system settings to Laravel config
        $this->applyGeneralSettings();
        $this->applyMailSettings();
        $this->applySessionSettings();

        // Share settings with all views
        $this->shareWithViews();

        return $next($request);
    }

    /**
     * Apply general system settings
     */
    protected function applyGeneralSettings(): void
    {
        // Apply system name
        $systemName = SystemSetting::get('system_name', config('app.name'));
        Config::set('app.name', $systemName);

        // Apply timezone
        $timezone = SystemSetting::get('timezone', config('app.timezone'));
        Config::set('app.timezone', $timezone);
        date_default_timezone_set($timezone);
        
        // IMPORTANT: Also configure Carbon to use the timezone
        \Carbon\Carbon::now()->setTimezone($timezone);

        // Apply default language
        // Check session first (user preference), then system default
        $language = session('user_language') ?? SystemSetting::get('default_language', 'id');
        Config::set('app.locale', $language);
        
        // Apply language to Laravel application
        app()->setLocale($language);
        
        // Apply language to Carbon for localized date formatting
        \Carbon\Carbon::setLocale($language === 'id' ? 'id_ID' : 'en_US');
    }

    /**
     * Apply email/mail settings
     */
    protected function applyMailSettings(): void
    {
        // Apply mail configuration from system settings
        $mailDriver = SystemSetting::get('mail_driver', 'smtp');
        $mailHost = SystemSetting::get('mail_host', 'localhost');
        $mailPort = SystemSetting::get('mail_port', 587);
        $mailUsername = SystemSetting::get('mail_username', '');
        $mailPassword = SystemSetting::get('mail_password', '');
        $mailEncryption = SystemSetting::get('mail_encryption', 'tls');
        $mailFromAddress = SystemSetting::get('system_email', config('mail.from.address'));
        $mailFromName = SystemSetting::get('system_name', config('mail.from.name'));

        Config::set('mail.default', $mailDriver);
        Config::set('mail.from.address', $mailFromAddress);
        Config::set('mail.from.name', $mailFromName);

        // SMTP specific settings
        if ($mailDriver === 'smtp') {
            Config::set('mail.mailers.smtp.host', $mailHost);
            Config::set('mail.mailers.smtp.port', $mailPort);
            Config::set('mail.mailers.smtp.encryption', $mailEncryption);
            Config::set('mail.mailers.smtp.username', $mailUsername);
            Config::set('mail.mailers.smtp.password', $mailPassword);
        }
    }

    /**
     * Apply session settings
     */
    protected function applySessionSettings(): void
    {
        // Apply session timeout from system settings
        $sessionTimeout = SystemSetting::get('session_timeout', 120);
        Config::set('session.lifetime', $sessionTimeout);
    }

    /**
     * Share system settings with all views
     */
    protected function shareWithViews(): void
    {
        // Get commonly needed settings
        $systemName = SystemSetting::get('system_name', config('app.name'));
        $systemEmail = SystemSetting::get('system_email', config('mail.from.address'));
        $timezone = SystemSetting::get('timezone', config('app.timezone'));
        $itemsPerPage = SystemSetting::get('items_per_page', 15);

        // Share with all Blade views
        View::share('systemName', $systemName);
        View::share('systemEmail', $systemEmail);
        View::share('systemTimezone', $timezone);
        View::share('defaultItemsPerPage', $itemsPerPage);

        // For Inertia views, we'll use HandleInertiaRequests middleware
    }
}
