<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Services\AuthService;

// Add these traits for admin relationships
use App\Models\Ticket;
use App\Models\Aplikasi;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            $role = app(AuthService::class)->getUserRole($user);

            // Get profile completion with error handling
            $profileCompletion = $this->getProfileCompletion($user);

            // Get user stats with error handling
            $stats = [];
            try {
                $stats = $this->getUserStats($user, $role);
            } catch (\Exception $e) {
                Log::error('Error getting user stats in profile: ' . $e->getMessage());
                $stats = [];
            }

            return Inertia::render('Profile/Index', [
                'user' => $user,
                'role' => $role,
                'profileCompletion' => $profileCompletion,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProfileController@index: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            // Return with a basic profile view if there's an error
            $user = Auth::user();
            $role = $user ? app(AuthService::class)->getUserRole($user) : 'user';

            return Inertia::render('Profile/Index', [
                'user' => $user,
                'role' => $role,
                'profileCompletion' => [
                    'basic_info' => 0,
                    'contact_info' => 0,
                    'overall' => 0,
                ],
                'stats' => [],
            ]);
        }
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            $role = app(AuthService::class)->getUserRole($user);

            return Inertia::render('Profile/Edit', [
                'user' => $user,
                'role' => $role,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProfileController@edit: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            // Return with a basic edit view if there's an error
            $user = Auth::user();
            $role = $user ? app(AuthService::class)->getUserRole($user) : 'user';

            return Inertia::render('Profile/Edit', [
                'user' => $user,
                'role' => $role,
            ]);
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $table = $this->getUserTable();

        // Validate with correct primary key column name
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:' . $table . ',email,' . $user->nip . ',nip',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
        ]);

        // Handle field name mapping for backward compatibility
        if (isset($validated['name'])) {
            $validated['nama_lengkap'] = $validated['name'];
        }

        $user->update($validated);

        // Get role prefix for redirect
        $role = app(\App\Services\AuthService::class)->getUserRole($user);
        $rolePrefix = $this->getRolePrefix($role);

        return redirect()->route($rolePrefix . '.profile.index')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Get role prefix for routes
     */
    private function getRolePrefix(string $role): string
    {
        return match($role) {
            'admin_helpdesk' => 'admin',
            'admin_aplikasi' => 'admin-aplikasi',
            'teknisi' => 'teknisi',
            'user' => 'user',
            default => 'user',
        };
    }

    
    /**
     * Get profile completion percentage.
     */
    private function getProfileCompletion($user): array
    {
        try {
            $completion = [
                'basic_info' => 0,
                'contact_info' => 0,
                'overall' => 0,
            ];

            if (!$user) {
                return $completion;
            }

            // Check for name field (could be 'name' or 'nama_lengkap')
            $nameFields = ['name', 'nama_lengkap'];
            $nameFound = false;
            foreach ($nameFields as $field) {
                if (!empty($user->{$field})) {
                    $nameFound = true;
                    break;
                }
            }

            $fields = ['email'];
            $filledFields = $nameFound ? 1 : 0;
            foreach ($fields as $field) {
                if (!empty($user->{$field})) {
                    $filledFields++;
                }
            }
            $completion['basic_info'] = ($filledFields / (count($fields) + 1)) * 100; // +1 for name

            $contactFields = ['phone', 'department', 'position'];
            $filledContactFields = 0;
            foreach ($contactFields as $field) {
                if (!empty($user->{$field})) {
                    $filledContactFields++;
                }
            }
            $completion['contact_info'] = ($filledContactFields / count($contactFields)) * 100;

            $totalFields = (count($fields) + 1) + count($contactFields); // +1 for name
            $totalFilled = $filledFields + $filledContactFields;
            $completion['overall'] = $totalFields > 0 ? ($totalFilled / $totalFields) * 100 : 0;

            return $completion;
        } catch (\Exception $e) {
            Log::error('Error in getProfileCompletion: ' . $e->getMessage());
            return [
                'basic_info' => 0,
                'contact_info' => 0,
                'overall' => 0,
            ];
        }
    }

    /**
     * Get the appropriate user table based on role.
     */
    private function getUserTable(): string
    {
        $role = app(AuthService::class)->getUserRole(Auth::user());

        return match($role) {
            'user' => 'users',
            'admin_helpdesk' => 'admin_helpdesks',
            'admin_aplikasi' => 'admin_aplikasis',
            'teknisi' => 'teknisis',
            default => 'users',
        };
    }

    /**
     * Get user statistics for internal use.
     */
    private function getUserStats($user, string $role): array
    {
        $stats = [];

        switch ($role) {
            case 'user':
                $stats = [
                    'tickets_created' => $user->tickets()->count(),
                    'tickets_resolved' => $user->tickets()->where('status', 'resolved')->count(),
                    'avg_resolution_time' => $user->tickets()
                        ->where('status', 'resolved')
                        ->whereNotNull('resolution_time_minutes')
                        ->avg('resolution_time_minutes') ?? 0,
                ];
                break;

            case 'teknisi':
                $stats = [
                    'tickets_assigned' => $user->assignedTickets()->count(),
                    'tickets_resolved' => $user->assignedTickets()->where('status', 'resolved')->count(),
                    'avg_rating' => $user->assignedTickets()
                        ->whereNotNull('user_rating')
                        ->avg('user_rating') ?? 0,
                    'resolution_rate' => $this->calculateResolutionRate($user),
                ];
                break;

            case 'admin_helpdesk':
                $stats = [
                    'tickets_managed' => $user->ticketsAssigned()->count(),
                    'users_managed' => User::count(),
                    'avg_response_time' => $this->calculateAvgResponseTime(),
                ];
                break;

            case 'admin_aplikasi':
                // Get managed applications count from multiple sources
                $managedCount = 0;
                if ($user->managed_applications && is_array($user->managed_applications)) {
                    $managedCount = count($user->managed_applications);
                }
                $directCount = \App\Models\Aplikasi::where('admin_aplikasi_nip', $user->nip)->count();
                $backupCount = \App\Models\Aplikasi::where('backup_admin_nip', $user->nip)->count();
                $totalManagedCount = max($managedCount, $directCount + $backupCount);
                
                $stats = [
                    'applications_managed' => $totalManagedCount,
                    'categories_managed' => 0, // AdminAplikasi doesn't have managedCategories method
                    'tickets_per_app' => $this->getTicketsPerApplication($user),
                ];
                break;
        }

        return $stats;
    }

    /**
     * Get user statistics for profile.
     */
    public function getStats()
    {
        $user = Auth::user();
        $role = app(AuthService::class)->getUserRole($user);

        $stats = [];

        switch ($role) {
            case 'user':
                $stats = [
                    'tickets_created' => $user->tickets()->count(),
                    'tickets_resolved' => $user->tickets()->where('status', 'resolved')->count(),
                    'avg_resolution_time' => $user->tickets()
                        ->where('status', 'resolved')
                        ->whereNotNull('resolution_time_minutes')
                        ->avg('resolution_time_minutes') ?? 0,
                ];
                break;

            case 'teknisi':
                $stats = [
                    'tickets_assigned' => $user->assignedTickets()->count(),
                    'tickets_resolved' => $user->assignedTickets()->where('status', 'resolved')->count(),
                    'avg_rating' => $user->assignedTickets()
                        ->whereNotNull('user_rating')
                        ->avg('user_rating') ?? 0,
                    'resolution_rate' => $this->calculateResolutionRate($user),
                ];
                break;

            case 'admin_helpdesk':
                $stats = [
                    'tickets_managed' => $user->ticketsAssigned()->count(),
                    'users_managed' => User::count(),
                    'avg_response_time' => $this->calculateAvgResponseTime(),
                ];
                break;

            case 'admin_aplikasi':
                // Get managed applications count from multiple sources
                $managedCount = 0;
                if ($user->managed_applications && is_array($user->managed_applications)) {
                    $managedCount = count($user->managed_applications);
                }
                $directCount = \App\Models\Aplikasi::where('admin_aplikasi_nip', $user->nip)->count();
                $backupCount = \App\Models\Aplikasi::where('backup_admin_nip', $user->nip)->count();
                $totalManagedCount = max($managedCount, $directCount + $backupCount);
                
                $stats = [
                    'applications_managed' => $totalManagedCount,
                    'categories_managed' => 0, // AdminAplikasi doesn't have managedCategories method
                    'tickets_per_app' => $this->getTicketsPerApplication($user),
                ];
                break;
        }

        return response()->json($stats);
    }

    /**
     * Calculate resolution rate for teknisi.
     */
    private function calculateResolutionRate($teknisi): float
    {
        $totalAssigned = $teknisi->assignedTickets()->count();
        $resolved = $teknisi->assignedTickets()->where('status', 'resolved')->count();

        return $totalAssigned > 0 ? ($resolved / $totalAssigned) * 100 : 0;
    }

    /**
     * Calculate average response time for admin helpdesk.
     */
    private function calculateAvgResponseTime(): float
    {
        // This would need to be implemented based on your business logic
        return 2.5; // placeholder
    }

    /**
     * Get tickets per application for admin aplikasi.
     */
    private function getTicketsPerApplication($admin): array
    {
        // This would need to be implemented based on your business logic
        return []; // placeholder
    }
}