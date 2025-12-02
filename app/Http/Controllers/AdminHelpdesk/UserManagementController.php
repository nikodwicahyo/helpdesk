<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Services\AuthService;
use App\Services\AuditLogService;
use Carbon\Carbon;
use \Illuminate\Database\Eloquent\Builder;
use \Illuminate\Pagination\LengthAwarePaginator;

class UserManagementController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display a listing of all users with role filtering and pagination.
     */
    public function index()
    {
        $admin = Auth::user();
        $request = request();

        // Get filter parameters
        $filters = $request->only([
            'role', 'status', 'department', 'search', 'sort_by', 'sort_direction', 'page'
        ]);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'name';
            $filters['sort_direction'] = 'asc';
        }

        // Get paginated users with filters
        $perPage = $request->get('per_page', 15);
        $users = $this->getFilteredUsers($filters, $perPage);

        // Format users for frontend
        $formattedUsers = $users->getCollection()->map(function ($user) {
            // Get role using the actual_role property or detect from table type
            $role = $user->actual_role ?? $this->detectRoleFromTable($user);

            return [
                'id' => $user->id,
                'nip' => $user->nip,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'department' => $user->department,
                'position' => $user->position,
                'status' => $user->status,
                'status_badge' => $this->getStatusBadge($user->status),
                'role' => $role,
                'role_label' => $this->getRoleLabel($role),
                'table_type' => $user->table_type ?? 'users',
                'profile_photo_url' => $user->profile_photo_url ?? null,
                'last_login_at' => $user->last_login_at,
                'formatted_last_login' => $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'Never',
                'is_locked' => method_exists($user, 'isLocked') ? $user->isLocked() : false,
                'login_attempts' => $user->login_attempts ?? 0,
                'created_at' => $user->created_at,
                'formatted_created_at' => $user->created_at->format('d M Y'),
                'can_edit' => $this->canEditUser($user),
                'can_toggle_status' => $this->canToggleUserStatus($user),
                'dashboard_stats' => $this->getUserDashboardStats($user, $role),
            ];
        });

        // Create new paginator with formatted data
        $formattedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $formattedUsers,
            $users->total(),
            $users->perPage(),
            $users->currentPage(),
            ['path' => $users->path(), 'pageName' => 'page']
        );

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        // Debug logging
        Log::info('UserManagementController returning data', [
            'users_total' => $formattedPaginator->total(),
            'users_current_page' => $formattedPaginator->currentPage(),
            'users_per_page' => $formattedPaginator->perPage(),
            'users_count' => $formattedPaginator->count(),
        ]);

        return Inertia::render('AdminHelpdesk/UserManagement', [
            'users' => $formattedPaginator,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return Inertia::render('AdminHelpdesk/UserManagement', [
            'departments' => $this->getDepartmentsList(),
            'positions' => $this->getPositionsList(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store()
    {
        $admin = Auth::user();
        $request = request();
        $nip = $request->nip;
        $email = $request->email;

        // Validate request data
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'role' => 'required|in:user,admin_helpdesk,admin_aplikasi,teknisi',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'status' => 'required|in:active,inactive',
        ]);

        // Check unique NIP and email across all user tables
        $validator->after(function ($validator) use ($nip, $email) {
            if ($this->findUserByNip($nip)) {
                $validator->errors()->add('nip', 'NIP already exists');
            }

            if ($this->findUserByEmail($email)) {
                $validator->errors()->add('email', 'Email already exists');
            }
        });

        if ($validator->fails()) {
            // Log validation failures for debugging
            \Log::error('User creation validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                    'message' => 'Validation failed',
                    'validation_errors' => $validator->errors()->toArray(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $role = $request->role;
            $baseData = [
                'nip' => $request->nip,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'password' => Hash::make($request->password),
                'status' => $request->status,
            ];

            // Create user based on role
            // Note: Activity logging is handled automatically by UserObserver/TeknisiObserver
            switch ($role) {
                case 'user':
                    $user = User::create($baseData);
                    Log::info('User created successfully', ['nip' => $user->nip, 'name' => $user->name]);
                    break;

                case 'admin_helpdesk':
                    $user = AdminHelpdesk::create($baseData);
                    Log::info('AdminHelpdesk created successfully', ['nip' => $user->nip, 'name' => $user->name]);
                    break;

                case 'admin_aplikasi':
                    $user = AdminAplikasi::create($baseData);
                    Log::info('AdminAplikasi created successfully', ['nip' => $user->nip, 'name' => $user->name]);
                    break;

                case 'teknisi':
                    $user = Teknisi::create(array_merge($baseData, [
                        'skill_level' => 'junior',
                        'department' => $request->department ?? 'IT Support',
                        'max_concurrent_tickets' => 10,
                    ]));
                    Log::info('Teknisi created successfully', ['nip' => $user->nip, 'name' => $user->name]);
                    break;

                default:
                    throw new \Exception("Unknown role: {$role}");
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully',
                    'user' => [
                        'id' => $user->id,
                        'nip' => $user->nip,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $role,
                        'role_label' => $this->getRoleLabel($role),
                        'status' => $user->status,
                        'status_badge' => $this->getStatusBadge($user->status),
                        'department' => $user->department,
                        'position' => $user->position,
                        'created_at' => $user->created_at,
                        'formatted_created_at' => $user->created_at->format('d M Y'),
                    ],
                ]);
            }

            return redirect()->route('admin.users.show', $user->nip)
                ->with('success', 'User created successfully');

        } catch (\Exception $e) {
            $errorMessage = 'Failed to create user: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => [$errorMessage],
                ], 500);
            }

            return back()
                ->withErrors([$errorMessage])
                ->withInput();
        }
    }

    /**
     * Display the specified user with their details and tickets.
     */
    public function show()
    {
        try {
            $nip = request()->route('nip');
            // Search for user across all tables
            $user = $this->findUserByNip($nip);

            if (!$user) {
                return redirect()->route('admin.users.index')
                    ->withErrors(['User not found']);
            }

            // Check access permissions
            if (!$this->canViewUser($user)) {
                return redirect()->route('admin.users.index')
                    ->withErrors(['You do not have permission to view this user']);
            }

            // Get user's recent tickets (only if user model has tickets relationship)
            $recentTickets = collect();
            $stats = [];

            if (method_exists($user, 'tickets')) {
                $recentTickets = $user->tickets()
                    ->with(['aplikasi', 'kategoriMasalah', 'assignedTeknisi'])
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(function ($ticket) {
                        return [
                            'id' => $ticket->id,
                            'ticket_number' => $ticket->ticket_number,
                            'title' => $ticket->title,
                            'status' => $ticket->status,
                            'status_label' => $ticket->status_label ?? $ticket->status,
                            'priority' => $ticket->priority,
                            'priority_label' => $ticket->priority_label ?? $ticket->priority,
                            'aplikasi' => $ticket->aplikasi ? [
                                'name' => $ticket->aplikasi->name,
                            ] : null,
                            'kategori_masalah' => $ticket->kategoriMasalah ? [
                                'name' => $ticket->kategoriMasalah->name,
                            ] : null,
                            'assigned_teknisi' => $ticket->assignedTeknisi ? [
                                'name' => $ticket->assignedTeknisi->name,
                            ] : null,
                            'created_at' => $ticket->created_at,
                            'formatted_created_at' => $ticket->created_at->format('d M Y, H:i'),
                        ];
                    });

                // Get user statistics
                $stats = [
                    'total_tickets' => $user->tickets()->count(),
                    'open_tickets' => $user->tickets()->where('status', 'open')->count(),
                    'in_progress_tickets' => $user->tickets()->where('status', 'in_progress')->count(),
                    'resolved_tickets' => $user->tickets()->where('status', 'resolved')->count(),
                    'closed_tickets' => $user->tickets()->where('status', 'closed')->count(),
                    'avg_tickets_per_month' => $this->calculateAvgTicketsPerMonth($user),
                ];
            }

            // Get user's activity timeline (recent tickets and logins)
            $activityTimeline = $this->getUserActivityTimeline($user, 20);

            $role = $this->detectRoleFromTable($user);

            return Inertia::render('AdminHelpdesk/UserManagement', [
                'user' => [
                    'id' => $user->id,
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'department' => $user->department,
                    'position' => $user->position,
                    'status' => $user->status,
                    'status_badge' => $this->getStatusBadge($user->status),
                    'role' => $role,
                    'role_label' => $this->getRoleLabel($role),
                    'table_type' => $user->getTable(),
                    'profile_photo_url' => $user->profile_photo_url ?? null,
                    'last_login_at' => $user->last_login_at,
                    'formatted_last_login' => $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'Never',
                    'created_at' => $user->created_at,
                    'formatted_created_at' => $user->created_at->format('d M Y, H:i'),
                    'can_edit' => $this->canEditUser($user),
                    'can_toggle_status' => $this->canToggleUserStatus($user),
                ],
                'recentTickets' => $recentTickets,
                'stats' => $stats,
                'activityTimeline' => $activityTimeline,
            ]);

        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')
                ->withErrors(['User not found: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit()
    {
        try {
            $nip = request()->route('nip');
            // Search for user across all tables
            $user = $this->findUserByNip($nip);

            if (!$user) {
                return redirect()->route('admin.users.index')
                    ->withErrors(['User not found']);
            }

            // Check access permissions
            if (!$this->canEditUser($user)) {
                return redirect()->route('admin.users.index')
                    ->withErrors(['You do not have permission to edit this user']);
            }

            $role = $this->detectRoleFromTable($user);

            return Inertia::render('AdminHelpdesk/UserManagement', [
                'user' => [
                    'id' => $user->id,
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'department' => $user->department,
                    'position' => $user->position,
                    'status' => $user->status,
                    'role' => $role,
                    'table_type' => $user->getTable(),
                ],
                'departments' => $this->getDepartmentsList(),
                'positions' => $this->getPositionsList(),
            ]);

        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')
                ->withErrors(['User not found: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified user.
     */
    public function update()
    {
        try {
            $request = request();
            $nip = $request->route('nip');

            // Search for user across all tables
            $user = $this->findUserByNip($nip);
            if (!$user) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['User not found'],
                    ], 404);
                }
                return back()->withErrors(['User not found'])->withInput();
            }

            // Check access permissions
            if (!$this->canEditUser($user)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['You do not have permission to edit this user'],
                    ], 403);
                }
                return back()->withErrors(['You do not have permission to edit this user'])->withInput();
            }

            $role = $this->detectRoleFromTable($user);

            $email = $request->email;

            // Validate request data
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'department' => 'nullable|string|max:100',
                'position' => 'nullable|string|max:100',
                'status' => 'required|in:active,inactive',
            ];

            // Only validate password if it's provided
            if ($request->filled('password')) {
                $rules['password'] = 'required|string|min:8|confirmed';
                $rules['password_confirmation'] = 'required|string|min:8';
            }

            $validator = Validator::make($request->all(), $rules);

            // Check unique email across all user tables
            $validator->after(function ($validator) use ($email, $user) {
                $existingUser = $this->findUserByEmail($email);
                if ($existingUser && $existingUser->nip !== $user->nip) {
                    $validator->errors()->add('email', 'Email already exists');
                }
            });

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()->all(),
                    ], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            // Store old values for logging
            $oldEmail = $user->email;
            $changes = [];
            
            // Update user data
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'status' => $request->status,
            ];

            // Update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
                $changes['password'] = 'changed';
            }

            $user->update($updateData);
            
            // Track changes for logging
            if ($user->wasChanged('email')) {
                $changes['email'] = ['old' => $oldEmail, 'new' => $user->email];
                AuditLogService::logEmailChanged($user, $oldEmail, $user->email);
            }
            if ($user->wasChanged('password')) {
                AuditLogService::logPasswordChanged($user);
            }
            if ($user->wasChanged()) {
                $changedFields = array_keys($user->getChanges());
                foreach ($changedFields as $field) {
                    if (!isset($changes[$field])) {
                        $changes[$field] = $user->$field;
                    }
                }
                AuditLogService::logUserUpdated($user, $role, $changes);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'user' => [
                        'id' => $user->id,
                        'nip' => $user->nip,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'department' => $user->department,
                        'position' => $user->position,
                        'status' => $user->status,
                        'status_badge' => $this->getStatusBadge($user->status),
                        'role' => $role,
                        'role_label' => $this->getRoleLabel($role),
                        'table_type' => $user->getTable(),
                        'can_edit' => $this->canEditUser($user),
                        'can_toggle_status' => $this->canToggleUserStatus($user),
                    ],
                ]);
            }

            return redirect()->route('admin.users.show', $user->nip)
                ->with('success', 'User updated successfully');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Failed to update user: ' . $e->getMessage()],
                ], 500);
            }

            return back()
                ->withErrors(['Failed to update user: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Deactivate user (soft delete).
     */
    public function destroy()
    {
        $admin = Auth::user();

        try {
            $nip = request()->route('nip');
            // Search for user across all tables
            $user = $this->findUserByNip($nip);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'errors' => ['User not found'],
                ], 404);
            }

            // Check access permissions
            if (!$this->canToggleUserStatus($user)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['You do not have permission to deactivate this user'],
                ], 422);
            }

            // Update user status to inactive
            $user->update(['status' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => 'User deactivated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to deactivate user: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Update user status (legacy method for compatibility).
     */
    public function updateStatus()
    {
        $admin = Auth::user();
        $request = request();
        $nip = $request->route('nip');
        $status = $request->status;

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $user = null;
        try {
            // Log the incoming request for debugging
            Log::info('User status update request', [
                'nip' => $nip,
                'requested_status' => $status,
                'admin_nip' => $admin->nip ?? 'unknown',
            ]);

            // Search for user across all tables
            $user = $this->findUserByNip($nip);

            if (!$user) {
                Log::warning('User not found for status update', ['nip' => $nip]);
                return response()->json([
                    'success' => false,
                    'errors' => ['User not found'],
                ], 404);
            }

            Log::info('User found for status update', [
                'nip' => $nip,
                'user_table' => $user->getTable(),
                'current_status' => $user->status,
                'user_class' => get_class($user),
            ]);

            // Check access permissions
            if (!$this->canToggleUserStatus($user)) {
                Log::warning('Permission denied for user status update', [
                    'nip' => $nip,
                    'admin_nip' => $admin->nip ?? 'unknown',
                ]);
                return response()->json([
                    'success' => false,
                    'errors' => ['You do not have permission to change this user\'s status'],
                ], 422);
            }

            // Use a database transaction to ensure data consistency
            DB::beginTransaction();

            $oldStatus = $user->status;

            Log::info('About to save user status change', [
                'nip' => $nip,
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);

            $user->update(['status' => $status]);

            Log::info('User status saved successfully', [
                'nip' => $nip,
                'new_status' => $user->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "User status changed from {$oldStatus} to {$status}",
                'user' => [
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'status' => $user->status,
                    'status_badge' => $this->getStatusBadge($user->status),
                ],
            ]);

        } catch (\Exception $e) {
            // Rollback transaction if there was an error
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            // Log the detailed error for debugging
            Log::error('User status update failed', [
                'nip' => $nip,
                'old_status' => $user ? $user->status : 'unknown',
                'new_status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_table' => $user ? $user->getTable() : 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to update user status: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Toggle user status (activate/deactivate).
     */
    public function toggleStatus()
    {
        $admin = Auth::user();
        $request = request();
        $nip = $request->route('nip');
        $status = $request->status;

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $user = null;
        try {
            // Log the incoming request for debugging
            Log::info('User status toggle request', [
                'nip' => $nip,
                'requested_status' => $status,
                'admin_nip' => $admin->nip ?? 'unknown',
            ]);

            // Search for user across all tables
            $user = $this->findUserByNip($nip);

            if (!$user) {
                Log::warning('User not found for status toggle', ['nip' => $nip]);
                return response()->json([
                    'success' => false,
                    'errors' => ['User not found'],
                ], 404);
            }

            Log::info('User found for status toggle', [
                'nip' => $nip,
                'user_table' => $user->getTable(),
                'current_status' => $user->status,
                'user_class' => get_class($user),
            ]);

            // Check access permissions
            if (!$this->canToggleUserStatus($user)) {
                Log::warning('Permission denied for user status toggle', [
                    'nip' => $nip,
                    'admin_nip' => $admin->nip ?? 'unknown',
                ]);
                return response()->json([
                    'success' => false,
                    'errors' => ['You do not have permission to change this user\'s status'],
                ], 422);
            }

            // Use a database transaction to ensure data consistency
            DB::beginTransaction();

            $oldStatus = $user->status;

            Log::info('About to save user status change', [
                'nip' => $nip,
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);

            $user->update(['status' => $status]);

            Log::info('User status saved successfully', [
                'nip' => $nip,
                'new_status' => $user->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "User status changed from {$oldStatus} to {$status}",
                'user' => [
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'status' => $user->status,
                    'status_badge' => $this->getStatusBadge($user->status),
                ],
            ]);

        } catch (\Exception $e) {
            // Rollback transaction if there was an error
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            // Log the detailed error for debugging
            Log::error('User status toggle failed', [
                'nip' => $nip,
                'old_status' => $user ? $user->status : 'unknown',
                'new_status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_table' => $user ? $user->getTable() : 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to toggle user status: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Get filtered users with advanced filtering across all user tables.
     */
    private function getFilteredUsers(array $filters = [], int $perPage = 15)
    {
        $currentAdmin = Auth::user();
        $currentAdminRole = $this->detectRoleFromTable($currentAdmin);

        // Get users from all tables
        $allUsers = collect();

        // Get regular users
        $usersQuery = User::query();
        $this->applyFiltersToQuery($usersQuery, $filters, 'user');
        $users = $usersQuery->get()->map(function ($user) {
            $user->table_type = 'users';
            $user->actual_role = 'user';
            return $user;
        });
        $allUsers = $allUsers->concat($users);

        // Get admin helpdesks (all admin helpdesk users should be visible)
        $adminHelpdesksQuery = AdminHelpdesk::query();
        $this->applyFiltersToQuery($adminHelpdesksQuery, $filters, 'admin_helpdesk');
        $adminHelpdesks = $adminHelpdesksQuery->get()->map(function ($admin) {
            $admin->table_type = 'admin_helpdesks';
            $admin->actual_role = 'admin_helpdesk';
            return $admin;
        });
        $allUsers = $allUsers->concat($adminHelpdesks);

        // Get admin aplikasis
        $adminAplikasisQuery = AdminAplikasi::query();
        $this->applyFiltersToQuery($adminAplikasisQuery, $filters, 'admin_aplikasi');
        $adminAplikasis = $adminAplikasisQuery->get()->map(function ($admin) {
            $admin->table_type = 'admin_aplikasis';
            $admin->actual_role = 'admin_aplikasi';
            return $admin;
        });
        $allUsers = $allUsers->concat($adminAplikasis);

        // Get teknisis
        $teknisisQuery = Teknisi::query();
        $this->applyFiltersToQuery($teknisisQuery, $filters, 'teknisi');
        $teknisis = $teknisisQuery->get()->map(function ($teknisi) {
            $teknisi->table_type = 'teknisis';
            $teknisi->actual_role = 'teknisi';
            return $teknisi;
        });
        $allUsers = $allUsers->concat($teknisis);

        // Apply sorting to the combined collection
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        if ($sortBy === 'name') {
            $allUsers = $sortDirection === 'desc'
                ? $allUsers->sortByDesc('name')->values()
                : $allUsers->sortBy('name')->values();
        } elseif ($sortBy === 'email') {
            $allUsers = $sortDirection === 'desc'
                ? $allUsers->sortByDesc('email')->values()
                : $allUsers->sortBy('email')->values();
        } elseif ($sortBy === 'department') {
            $allUsers = $sortDirection === 'desc'
                ? $allUsers->sortByDesc('department')->values()
                : $allUsers->sortBy('department')->values();
        } elseif ($sortBy === 'created_at') {
            $allUsers = $sortDirection === 'desc'
                ? $allUsers->sortByDesc('created_at')->values()
                : $allUsers->sortBy('created_at')->values();
        } elseif ($sortBy === 'last_login_at') {
            $allUsers = $sortDirection === 'desc'
                ? $allUsers->sortByDesc('last_login_at')->values()
                : $allUsers->sortBy('last_login_at')->values();
        }

        // Manually paginate the collection
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $perPage;
        $itemsForCurrentPage = $allUsers->slice($offset, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsForCurrentPage,
            $allUsers->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Apply filters to a user query based on role and other criteria.
     */
    private function applyFiltersToQuery($query, array $filters, string $role): void
    {
        // Apply role filter
        if (!empty($filters['role']) && $filters['role'] !== $role) {
            $query->whereRaw('1 = 0'); // Return no results if role filter doesn't match
            return;
        }

        // Apply status filter
        if (!empty($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        // Apply department filter
        if (!empty($filters['department'] ?? null)) {
            $query->where('department', $filters['department']);
        }

        // Apply search filter
        if (!empty($filters['search'] ?? null)) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('nip', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('department', 'like', "%{$filters['search']}%");
            });
        }
    }

    /**
     * Get filter options for users listing.
     */
    private function getFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
            'roles' => [
                ['value' => 'user', 'label' => 'User'],
                ['value' => 'teknisi', 'label' => 'Teknisi'],
                ['value' => 'admin_helpdesk', 'label' => 'Admin Helpdesk'],
                ['value' => 'admin_aplikasi', 'label' => 'Admin Aplikasi'],
            ],
            'departments' => $this->getAllDepartments(),
        ];
    }

    /**
     * Get all departments from all user tables.
     */
    private function getAllDepartments(): array
    {
        // Get departments from all user tables
        $userDepartments = User::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $adminHelpdeskDepartments = AdminHelpdesk::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $adminAplikasiDepartments = AdminAplikasi::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $teknisiDepartments = Teknisi::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        // Merge and deduplicate all departments
        $allDepartments = $userDepartments->concat($adminHelpdeskDepartments)
            ->concat($adminAplikasiDepartments)
            ->concat($teknisiDepartments)
            ->unique()
            ->sort()
            ->map(function ($dept) {
                return ['value' => $dept, 'label' => $dept];
            })
            ->toArray();

        return $allDepartments;
    }

    /**
     * Get departments list for forms.
     */
    private function getDepartmentsList(): array
    {
        // Get departments from all user tables
        $userDepartments = User::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $adminHelpdeskDepartments = AdminHelpdesk::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $adminAplikasiDepartments = AdminAplikasi::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $teknisiDepartments = Teknisi::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        // Merge and deduplicate all departments
        return $userDepartments->concat($adminHelpdeskDepartments)
            ->concat($adminAplikasiDepartments)
            ->concat($teknisiDepartments)
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get positions list for forms.
     */
    private function getPositionsList(): array
    {
        // Get positions from all user tables
        $userPositions = User::select('position')
            ->whereNotNull('position')
            ->distinct()
            ->orderBy('position')
            ->pluck('position');

        $adminHelpdeskPositions = AdminHelpdesk::select('position')
            ->whereNotNull('position')
            ->distinct()
            ->orderBy('position')
            ->pluck('position');

        $adminAplikasiPositions = AdminAplikasi::select('position')
            ->whereNotNull('position')
            ->distinct()
            ->orderBy('position')
            ->pluck('position');

        $teknisiPositions = Teknisi::select('position')
            ->whereNotNull('position')
            ->distinct()
            ->orderBy('position')
            ->pluck('position');

        // Merge and deduplicate all positions
        return $userPositions->concat($adminHelpdeskPositions)
            ->concat($adminAplikasiPositions)
            ->concat($teknisiPositions)
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get role label for display.
     */
    private function getRoleLabel($role): string
    {
        switch ($role) {
            case 'user':
                return 'User';
            case 'teknisi':
                return 'Teknisi';
            case 'admin_helpdesk':
                return 'Admin Helpdesk';
            case 'admin_aplikasi':
                return 'Admin Aplikasi';
            default:
                return ucfirst($role);
        }
    }

    /**
     * Calculate average tickets per month for a user.
     */
    private function calculateAvgTicketsPerMonth($user): float
    {
        $firstTicket = $user->tickets()->orderBy('created_at', 'asc')->first();

        if (!$firstTicket) {
            return 0;
        }

        $monthsActive = max(1, Carbon::now()->diffInMonths($firstTicket->created_at) + 1);
        $totalTickets = $user->tickets()->count();

        return round($totalTickets / $monthsActive, 1);
    }

    /**
     * Get user activity timeline.
     */
    private function getUserActivityTimeline($user, int $limit = 20): array
    {
        $activities = [];

        // Recent tickets
        $recentTickets = $user->tickets()
            ->latest()
            ->limit(10)
            ->get();

        foreach ($recentTickets as $ticket) {
            $activities[] = [
                'type' => 'ticket_created',
                'title' => 'Created ticket',
                'description' => "Ticket #{$ticket->ticket_number}: {$ticket->title}",
                'date' => $ticket->created_at,
                'formatted_date' => $ticket->created_at->diffForHumans(),
                'icon' => 'ticket',
                'color' => 'blue',
            ];
        }

        // Recent logins
        if ($user->last_login_at) {
            $activities[] = [
                'type' => 'login',
                'title' => 'Last login',
                'description' => 'User logged into the system',
                'date' => $user->last_login_at,
                'formatted_date' => $user->last_login_at->diffForHumans(),
                'icon' => 'login',
                'color' => 'green',
            ];
        }

        // Sort by date (newest first)
        usort($activities, function ($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        // Apply the limit parameter
        return array_slice($activities, 0, $limit);
    }

    /**
     * Get user statistics for API consumption.
     */
    public function getStats()
    {
        // Count all users across all tables
        $totalUsers = User::count() + AdminHelpdesk::count() + AdminAplikasi::count() + Teknisi::count();
        $activeUsers = User::where('status', 'active')->count() +
                       AdminHelpdesk::where('status', 'active')->count() +
                       AdminAplikasi::where('status', 'active')->count() +
                       Teknisi::where('status', 'active')->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        // Department breakdown from all tables
        $departmentBreakdown = collect();

        // Get departments from each table
        $userDepartments = User::select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->get();

        $adminHelpdeskDepartments = AdminHelpdesk::select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->get();

        $adminAplikasiDepartments = AdminAplikasi::select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->get();

        $teknisiDepartments = Teknisi::select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->get();

        // Merge all department counts
        $allDepartments = $userDepartments->concat($adminHelpdeskDepartments)
            ->concat($adminAplikasiDepartments)
            ->concat($teknisiDepartments);

        $departmentBreakdown = $allDepartments->groupBy('department')
            ->map(function ($items) {
                return $items->sum('count');
            })
            ->sortDesc()
            ->toArray();

        // New users this month from all tables
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $newUsersThisMonth = User::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count() +
            AdminHelpdesk::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count() +
            AdminAplikasi::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count() +
            Teknisi::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $stats = [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'users_with_recent_activity' => $this->getUsersWithRecentActivity(7),
            'department_breakdown' => $departmentBreakdown,
            'new_users_this_month' => $newUsersThisMonth,
            'users_by_role' => [
                'user' => User::count(),
                'teknisi' => Teknisi::count(),
                'admin_helpdesk' => AdminHelpdesk::count(),
                'admin_aplikasi' => AdminAplikasi::count(),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Get users with recent activity across all tables.
     */
    private function getUsersWithRecentActivity(int $days): int
    {
        $cutoffDate = Carbon::now()->subDays($days);
        $count = 0;

        // Check if last_login_at column exists before querying
        try {
            $count += User::whereNotNull('last_login_at')->where('last_login_at', '>=', $cutoffDate)->count();
        } catch (\Exception $e) {
            // Column doesn't exist, skip
        }

        try {
            $count += AdminHelpdesk::whereNotNull('last_login_at')->where('last_login_at', '>=', $cutoffDate)->count();
        } catch (\Exception $e) {
            // Column doesn't exist, skip
        }

        try {
            $count += AdminAplikasi::whereNotNull('last_login_at')->where('last_login_at', '>=', $cutoffDate)->count();
        } catch (\Exception $e) {
            // Column doesn't exist, skip
        }

        try {
            $count += Teknisi::whereNotNull('last_login_at')->where('last_login_at', '>=', $cutoffDate)->count();
        } catch (\Exception $e) {
            // Column doesn't exist, skip
        }

        return $count;
    }

    /**
     * Import users from CSV file.
     */
    public function importCsv()
    {
        $admin = Auth::user();
        $request = request();

        // Validate file upload
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file upload',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('csv_file');
            $results = $this->processCsvImport($file);

            // Log the import operation
            AuditLogService::logDataImported('User', $results);

            return response()->json([
                'success' => true,
                'message' => 'CSV import completed',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download CSV template for user import.
     */
    public function downloadTemplate()
    {
        $filename = 'user_import_template_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add CSV header
            fputcsv($file, [
                'nip',
                'name',
                'email',
                'phone',
                'department',
                'position',
                'role',
                'password',
                'status'
            ]);

            // Add sample data
            fputcsv($file, [
                '199001012010011001',
                'John Doe',
                'john.doe@example.com',
                '08123456789',
                'IT Support',
                'Staff',
                'user',
                'SecurePass123',
                'active'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process CSV import and create users.
     */
    private function processCsvImport($file): array
    {
        $results = [
            'total_rows' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'errors' => [],
        ];

        // Check if file parameter is provided
        if (!$file) {
            throw new \Exception('No file provided for import');
        }

        $filePath = $file->getPathname();
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception('Failed to open CSV file');
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new \Exception('CSV file is empty or invalid');
        }

        // Validate required headers
        $requiredHeaders = ['nip', 'name', 'email', 'role', 'password'];
        $missingHeaders = array_diff($requiredHeaders, $header);

        if (!empty($missingHeaders)) {
            fclose($handle);
            throw new \Exception('Missing required columns: ' . implode(', ', $missingHeaders));
        }

        // Initialize rowNumber
        $rowNumber = 1; // Header is row 0

        // Begin database transaction
        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $results['total_rows']++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Combine header with row data
                    $userData = array_combine($header, $row);

                    // Validate and clean user data
                    $validatedUserData = $this->validateCsvRow($userData, $rowNumber);

                    // Check if validation failed
                    if (isset($validatedUserData['__validation_errors'])) {
                        $results['failed_imports']++;
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'data' => $userData,
                            'error' => implode('; ', $validatedUserData['__validation_errors']),
                        ];
                        continue;
                    }

                    if (empty($validatedUserData)) {
                        $results['failed_imports']++;
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'data' => $userData,
                            'error' => 'Validation failed with no specific errors',
                        ];
                        continue;
                    }

                    // Create user based on role
                    $this->createUserFromCsvData($validatedUserData, $rowNumber);
                    $results['successful_imports']++;

                } catch (\Exception $e) {
                    $results['failed_imports']++;
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'data' => $row,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            fclose($handle);

            // Commit transaction if there were successful imports
            if ($results['successful_imports'] > 0) {
                DB::commit();
            } else {
                DB::rollBack();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        return $results;
    }

    /**
     * Validate a single CSV row.
     */
    private function validateCsvRow(array $userData, int $rowNumber): array
    {
        $errors = [];

        // Validate NIP
        if (empty($userData['nip'])) {
            $errors[] = 'NIP is required';
        } elseif (strlen($userData['nip']) < 10) {
            $errors[] = 'NIP must be at least 10 characters';
        }

        // Validate name
        if (empty($userData['name'])) {
            $errors[] = 'Name is required';
        } elseif (strlen($userData['name']) > 255) {
            $errors[] = 'Name must be less than 255 characters';
        }

        // Validate email
        if (empty($userData['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Validate role
        $validRoles = ['user', 'admin_helpdesk', 'admin_aplikasi', 'teknisi'];
        if (empty($userData['role'])) {
            $errors[] = 'Role is required';
        } elseif (!in_array($userData['role'], $validRoles)) {
            $errors[] = 'Invalid role. Must be one of: ' . implode(', ', $validRoles);
        }

        // Validate password
        if (empty($userData['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($userData['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        // Validate status
        if (!empty($userData['status']) && !in_array($userData['status'], ['active', 'inactive'])) {
            $errors[] = 'Status must be either "active" or "inactive"';
        }

        // Check for duplicates
        if (!empty($userData['nip'])) {
            $existingUser = $this->findUserByNip($userData['nip']);
            if ($existingUser) {
                $errors[] = 'User with NIP ' . $userData['nip'] . ' already exists';
            }
        }

        if (!empty($userData['email'])) {
            $existingEmail = $this->findUserByEmail($userData['email']);
            if ($existingEmail) {
                $errors[] = 'User with email ' . $userData['email'] . ' already exists';
            }
        }

        if (!empty($errors)) {
            // Log errors for this row
            Log::warning("CSV Import Row {$rowNumber} validation failed", [
                'row_number' => $rowNumber,
                'errors' => $errors,
                'data' => $userData
            ]);
            // Return errors in a special array format so we can capture them
            return ['__validation_errors' => $errors];
        }

        return $userData;
    }

    /**
     * Create user from validated CSV data.
     */
    private function createUserFromCsvData(array $userData, int $rowNumber)
    {
        // Initialize $role and $baseData from $userData
        $role = $userData['role'];
        $baseData = [
            'nip' => $userData['nip'],
            'name' => trim($userData['name']),
            'email' => trim($userData['email']),
            'phone' => $userData['phone'] ?? null,
            'department' => $userData['department'] ?? null,
            'position' => $userData['position'] ?? null,
            'password' => Hash::make($userData['password']),
            'status' => $userData['status'] ?? 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        switch ($role) {
            case 'user':
                $user = User::create($baseData);
                AuditLogService::logUserCreated($user, 'User');
                break;

            case 'admin_helpdesk':
                $user = AdminHelpdesk::create(array_merge($baseData, [
                    'admin_helpdesk' => true,
                ]));
                AuditLogService::logUserCreated($user, 'AdminHelpdesk');
                break;

            case 'admin_aplikasi':
                $user = AdminAplikasi::create(array_merge($baseData, [
                    'admin_aplikasi' => true,
                ]));
                AuditLogService::logUserCreated($user, 'AdminAplikasi');
                break;

            case 'teknisi':
                $user = Teknisi::create(array_merge($baseData, [
                    'teknisi' => true,
                    'skill_level' => $userData['skill_level'] ?? 'junior',
                    'department' => $userData['department'] ?? 'IT Support',
                    'max_concurrent_tickets' => $userData['max_concurrent_tickets'] ?? 10,
                ]));
                AuditLogService::logUserCreated($user, 'Teknisi');
                break;

            default:
                throw new \Exception("Unknown role: {$role}");
        }

        // Log successful creation
        Log::info("User created via CSV import", [
            'row_number' => $rowNumber,
            'nip' => $userData['nip'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => $role,
            'imported_by' => Auth::user()->nip,
        ]);
    }

    /**
     * Check if NIP is available (for admin user creation)
     */
    public function checkNip(Request $request)
    {
        $nip = $request->input('nip');

        if (empty($nip)) {
            return response()->json([
                'valid' => false,
                'available' => true,
                'message' => 'NIP wajib diisi'
            ]);
        }

        // Clean NIP
        $cleanedNip = preg_replace('/\D/', '', $nip);

        // Use validation rules to check NIP
        $validator = Validator::make(['nip' => $cleanedNip], [
            'nip' => ['required', 'string', new \App\Rules\ValidNipFormat()]
        ]);

        // Check format validity first
        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'available' => false,
                'message' => $validator->errors()->first('nip')
            ]);
        }

        // Check uniqueness across all tables
        $validator = Validator::make(['nip' => $cleanedNip], [
            'nip' => [new \App\Rules\UniqueNipAcrossTables()]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => true,
                'available' => false,
                'message' => $validator->errors()->first('nip')
            ]);
        }

        return response()->json([
            'valid' => true,
            'available' => true,
            'message' => 'NIP valid dan tersedia'
        ]);
    }

    /**
     * Check if email is available (for admin user creation)
     */
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');

        if (empty($email)) {
            return response()->json([
                'valid' => false,
                'available' => true,
                'message' => 'Email wajib diisi'
            ]);
        }

        // Clean email
        $cleanedEmail = strtolower(trim($email));

        // Use validation rules to check email format first
        $validator = Validator::make(['email' => $cleanedEmail], [
            'email' => ['required', 'email', 'max:255']
        ]);

        // Check format validity
        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'available' => false,
                'message' => 'Format email tidak valid'
            ]);
        }

        // Check uniqueness across all tables using custom rule
        $validator = Validator::make(['email' => $cleanedEmail], [
            'email' => [new \App\Rules\UniqueEmailAcrossTables()]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => true,
                'available' => false,
                'message' => $validator->errors()->first('email')
            ]);
        }

        return response()->json([
            'valid' => true,
            'available' => true,
            'message' => 'Email valid dan tersedia'
        ]);
    }

    /**
     * Find user by NIP across all user tables.
     */
    private function findUserByNip(string $nip)
    {
        return User::where('nip', $nip)->first()
            ?? AdminHelpdesk::where('nip', $nip)->first()
            ?? AdminAplikasi::where('nip', $nip)->first()
            ?? Teknisi::where('nip', $nip)->first();
    }

    /**
     * Find user by email across all user tables.
     */
    private function findUserByEmail(string $email)
    {
        return User::where('email', $email)->first()
            ?? AdminHelpdesk::where('email', $email)->first()
            ?? AdminAplikasi::where('email', $email)->first()
            ?? Teknisi::where('email', $email)->first();
    }

    /**
     * Detect user role from table type.
     */
    private function detectRoleFromTable($user): string
    {
        // Handle null user
        if (!$user) {
            return 'user';
        }

        // Handle authenticated users by checking their class
        if ($user instanceof \App\Models\User) {
            return 'user';
        } elseif ($user instanceof \App\Models\AdminHelpdesk) {
            return 'admin_helpdesk';
        } elseif ($user instanceof \App\Models\AdminAplikasi) {
            return 'admin_aplikasi';
        } elseif ($user instanceof \App\Models\Teknisi) {
            return 'teknisi';
        }

        // For collections with table_type property
        $tableType = $user->table_type ?? (method_exists($user, 'getTable') ? $user->getTable() : 'users');

        switch ($tableType) {
            case 'users':
                return 'user';
            case 'admin_helpdesks':
                return 'admin_helpdesk';
            case 'admin_aplikasis':
                return 'admin_aplikasi';
            case 'teknisis':
                return 'teknisi';
            default:
                return 'user';
        }
    }

    /**
     * Get status badge class for user.
     */
    private function getStatusBadge(string $status): string
    {
        switch ($status) {
            case 'active':
                return 'bg-green-100 text-green-800';
            case 'inactive':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Get role badge class for user.
     */
    private function getRoleBadge(string $role): string
    {
        switch ($role) {
            case 'user':
                return 'bg-blue-100 text-blue-800';
            case 'admin_helpdesk':
                return 'bg-red-100 text-red-800';
            case 'admin_aplikasi':
                return 'bg-purple-100 text-purple-800';
            case 'teknisi':
                return 'bg-green-100 text-green-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Check if current admin can view the user.
     */
    private function canViewUser($user): bool
    {
        $currentAdmin = Auth::user();
        $currentRole = $this->detectRoleFromTable($currentAdmin);

        // All admin helpdesk users can view all users (including other admin helpdesk)
        if ($currentRole === 'admin_helpdesk') {
            return true;
        }

        return false; // Other roles shouldn't access this controller
    }

    /**
     * Check if current admin can edit the user.
     */
    private function canEditUser($user): bool
    {
        $currentAdmin = Auth::user();
        $currentRole = $this->detectRoleFromTable($currentAdmin);
        $targetRole = $this->detectRoleFromTable($user);

        // Admin helpdesk cannot edit other admin helpdesk users
        if ($currentRole === 'admin_helpdesk' && $targetRole === 'admin_helpdesk') {
            return false;
        }

        // Cannot edit self
        if ($currentAdmin->nip === $user->nip) {
            return false;
        }

        return $this->canViewUser($user);
    }

    /**
     * Check if current admin can toggle user status.
     */
    private function canToggleUserStatus($user): bool
    {
        $currentAdmin = Auth::user();
        $currentRole = $this->detectRoleFromTable($currentAdmin);
        $targetRole = $this->detectRoleFromTable($user);

        // Admin helpdesk cannot toggle status of other admin helpdesk users
        if ($currentRole === 'admin_helpdesk' && $targetRole === 'admin_helpdesk') {
            return false;
        }

        // Cannot toggle self status
        if ($currentAdmin->nip === $user->nip) {
            return false;
        }

        return true;
    }

    /**
     * Get user dashboard stats based on role.
     */
    private function getUserDashboardStats($user, string $role): array
    {
        // For now, return basic stats. This can be enhanced later
        return [
            'total_tickets' => method_exists($user, 'tickets') ? $user->tickets()->count() : 0,
            'active_tickets' => 0,
            'resolved_tickets' => 0,
            'unread_notifications' => 0,
        ];
    }

    /**
     * Find user by NIP and table type.
     */
    private function findUserByNipAndTable(string $nip, string $tableType)
    {
        switch ($tableType) {
            case 'users':
                return User::where('nip', $nip)->first();
            case 'admin_helpdesks':
                return AdminHelpdesk::where('nip', $nip)->first();
            case 'admin_aplikasis':
                return AdminAplikasi::where('nip', $nip)->first();
            case 'teknisis':
                return Teknisi::where('nip', $nip)->first();
            default:
                return null;
        }
    }

    /**
     * Generate a secure temporary password.
     */
    private function generateSecurePassword(string $userName): string
    {
        // Extract letters from user name for personalization
        $nameLetters = strtoupper(preg_replace('/[^A-Za-z]/', '', $userName));
        $namePrefix = substr($nameLetters, 0, 3);

        // If name has less than 3 letters, use default prefix
        if (strlen($namePrefix) < 3) {
            $namePrefix = 'USR';
        }

        // Generate secure components
        $datePart = now()->format('ymd'); // YYMMDD format
        $randomPart1 = strtoupper(substr(bin2hex(random_bytes(2)), 0, 3)); // 3 random chars
        $randomPart2 = rand(100, 999); // 3 random digits

        // Combine parts: Name(3) + Date(6) + Random(3) + Digits(3) = 15 characters
        $password = $namePrefix . $datePart . $randomPart1 . $randomPart2;

        // Validate password strength
        if (!$this->isPasswordStrong($password)) {
            // If password is not strong enough, regenerate
            return $this->generateSecurePassword($userName);
        }

        return $password;
    }

    /**
     * Check if password meets security requirements.
     */
    private function isPasswordStrong(string $password): bool
    {
        // Minimum length of 12 characters
        if (strlen($password) < 12) {
            return false;
        }

        // Check for uppercase letters (should have at least 6)
        $uppercaseCount = preg_match_all('/[A-Z]/', $password);
        if ($uppercaseCount < 6) {
            return false;
        }

        // Check for digits (should have at least 3)
        $digitCount = preg_match_all('/[0-9]/', $password);
        if ($digitCount < 3) {
            return false;
        }

        // Check for complexity (mix of letters and numbers)
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Reset user password.
     */
    public function resetPassword(string $nip)
    {
        try {
            $request = request();
            // Log the reset password attempt for debugging
            Log::info('Password reset attempt', [
                'nip' => $nip,
                'admin_nip' => Auth::user()->nip ?? 'unknown',
                'timestamp' => now()->toISOString()
            ]);

            // Search for user across all tables
            $user = $this->findUserByNip($nip);

            if (!$user) {
                Log::warning('User not found for password reset', ['nip' => $nip]);
                return response()->json([
                    'success' => false,
                    'errors' => ['User not found'],
                ], 404);
            }

            // Check access permissions
            if (!$this->canEditUser($user)) {
                Log::warning('Permission denied for password reset', [
                    'nip' => $nip,
                    'admin_nip' => Auth::user()->nip ?? 'unknown',
                    'user_role' => $this->detectRoleFromTable($user)
                ]);
                return response()->json([
                    'success' => false,
                    'errors' => ['You do not have permission to reset this user\'s password'],
                ], 422);
            }

            // Generate a secure default password with better security
            $newPassword = $this->generateSecurePassword($user->name);

            // Prepare update data
            $updateData = [
                'password' => Hash::make($newPassword),
                'updated_at' => now(),
            ];

            // Reset login attempts and clear any lockout for all user types
            // Since we've added the columns to all user tables, we can safely update them
            $updateData['login_attempts'] = 0;
            $updateData['locked_until'] = null;

            // Update user password with error handling
            try {
                $user->update($updateData);
                Log::info('Password reset successful', [
                    'nip' => $nip,
                    'user_name' => $user->name,
                    'admin_nip' => Auth::user()->nip ?? 'unknown'
                ]);
            } catch (\Exception $updateError) {
                Log::error('Failed to update user password', [
                    'nip' => $nip,
                    'error' => $updateError->getMessage(),
                    'trace' => $updateError->getTraceAsString()
                ]);
                throw new \Exception('Failed to update user password: ' . $updateError->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => "Password for {$user->name} has been reset successfully",
                'new_password' => $newPassword,
                'user_info' => [
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $this->detectRoleFromTable($user),
                ],
                'password_info' => [
                    'password' => $newPassword,
                    'format' => 'First3LettersOfName + YYMMDD + 3RandomChars + 3Digits (15 chars)',
                    'example' => "JHN241111ABC123 (for John Doe)",
                    'length' => strlen($newPassword),
                    'strength' => 'Strong - Contains uppercase letters and numbers',
                    'security_level' => 'High - Meets minimum 12 chars, 6 uppercase, 3 digits',
                    'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
                    'instructions' => 'This is a temporary password. User should change it on first login.',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'nip' => $nip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_nip' => Auth::user()->nip ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to reset password: ' . $e->getMessage()],
                'debug_info' => [
                    'nip' => $nip,
                    'error_type' => get_class($e),
                    'error_code' => $e->getCode(),
                ]
            ], 500);
        }
    }

    /**
     * Export users to CSV/Excel.
     */
    public function export()
    {
        try {
            $request = request();
            $format = $request->get('format', 'csv');
            $filters = $request->only(['role', 'status', 'department', 'search']);

            // Get all users (without pagination for export)
            $users = $this->getFilteredUsers($filters, 1000);
            $allUsers = $users->getCollection();

            if ($format === 'csv') {
                $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ];

                $callback = function () use ($allUsers) {
                    $file = fopen('php://output', 'w');

                    // CSV header
                    fputcsv($file, [
                        'NIP',
                        'Name',
                        'Email',
                        'Phone',
                        'Department',
                        'Position',
                        'Role',
                        'Status',
                        'Last Login',
                        'Created At'
                    ]);

                    // Transform users to arrays to avoid type issues
                    $usersArray = $allUsers->map(function ($user) {
                        return [
                            'nip' => (string) $user->nip,
                            'name' => (string) $user->name,
                            'email' => (string) $user->email,
                            'phone' => (string) $user->phone,
                            'department' => (string) $user->department,
                            'position' => (string) $user->position,
                            'role' => (string) $this->detectRoleFromTable($user),
                            'status' => (string) $user->status,
                            'last_login' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never',
                            'created_at' => $user->created_at->format('Y-m-d H:i:s')
                        ];
                    })->toArray();

                    // Output CSV data
                    foreach ($usersArray as $userData) {
                        fputcsv($file, array_values($userData));
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }

            // For other formats (can be extended later)
            return back()->withErrors(['Export format not supported']);

        } catch (\Exception $e) {
            return back()->withErrors(['Failed to export users: ' . $e->getMessage()]);
        }
    }

    /**
     * Perform bulk actions on selected users.
     */
    public function bulkAction()
    {
        // Use global request() helper to avoid IDE issues
        $input = request()->all();
        
        $validator = Validator::make($input, [
            'action' => 'required|in:activate,deactivate,delete',
            'users' => 'required|array',
            'users.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $action = $input['action'];
            $nips = $input['users'];
            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($nips as $nip) {
                try {
                    $user = $this->findUserByNip($nip);

                    if (!$user) {
                        $results['failed']++;
                        $results['errors'][] = "User with NIP {$nip} not found";
                        continue;
                    }

                    // Check permissions for each action
                    if ($action === 'delete' && !$this->canToggleUserStatus($user)) {
                        $results['failed']++;
                        $results['errors'][] = "No permission to delete user {$user->name}";
                        continue;
                    }

                    switch ($action) {
                        case 'activate':
                            $user->update(['status' => 'active']);
                            $results['success']++;
                            break;

                        case 'deactivate':
                            if ($this->canToggleUserStatus($user)) {
                                $user->update(['status' => 'inactive']);
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Cannot deactivate user {$user->name}";
                            }
                            break;

                        case 'delete':
                            // Soft delete by setting status to inactive
                            if ($this->canToggleUserStatus($user)) {
                                $user->update(['status' => 'inactive']);
                                $results['success']++;
                            } else {
                                $results['failed']++;
                                $results['errors'][] = "Cannot delete user {$user->name}";
                            }
                            break;
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Error processing user with NIP {$nip}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Bulk action completed. Success: {$results['success']}, Failed: {$results['failed']}",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Bulk action failed: ' . $e->getMessage()],
            ], 500);
        }
    }
}
