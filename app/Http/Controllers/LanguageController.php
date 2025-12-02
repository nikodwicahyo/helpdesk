<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;

class LanguageController extends Controller
{
    /**
     * Switch application language
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(Request $request)
    {
        $request->validate([
            'language' => 'required|in:id,en',
        ]);
        
        $language = $request->input('language');
        
        // Store language preference in session
        Session::put('user_language', $language);
        
        // Optionally update system default language if user is admin
        if (auth()->check() && $this->isAdmin()) {
            SystemSetting::set('default_language', $language, 'general', 'Default language for the application');
            
            Log::info('System default language updated', [
                'user' => auth()->user()->nip,
                'language' => $language,
            ]);
        }
        
        Log::info('User language switched', [
            'user' => auth()->user()?->nip ?? 'guest',
            'language' => $language,
        ]);
        
        // Redirect back with success message
        return back()->with('success', $language === 'id' 
            ? 'Bahasa berhasil diubah ke Bahasa Indonesia' 
            : 'Language successfully changed to English'
        );
    }
    
    /**
     * Get current language
     * 
     * @return string
     */
    public function current()
    {
        return response()->json([
            'language' => $this->getCurrentLanguage(),
            'available' => ['id', 'en'],
        ]);
    }
    
    /**
     * Get current language from session or system settings
     * 
     * @return string
     */
    private function getCurrentLanguage(): string
    {
        // Check user session preference first
        if (Session::has('user_language')) {
            return Session::get('user_language');
        }
        
        // Fall back to system default
        return SystemSetting::get('default_language', 'id');
    }
    
    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    private function isAdmin(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Check if user has admin role
        if (isset($user->role)) {
            return in_array($user->role, ['admin_helpdesk', 'admin_aplikasi']);
        }
        
        // Check by model type
        return get_class($user) === 'App\Models\AdminHelpdesk' 
            || get_class($user) === 'App\Models\AdminAplikasi';
    }
}
