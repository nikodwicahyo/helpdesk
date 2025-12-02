<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Ticket;
use App\Models\Notification;
use App\Services\AuthService;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Primary key configuration
     */
    protected $primaryKey = 'nip';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nip',
        'name',
        'nama_lengkap', // Alias for name (backward compatibility)
        'email',
        'phone',
        'no_telepon', // Alias for phone (backward compatibility)
        'department',
        'unit_kerja', // Alias for department (backward compatibility)
        'position',
        'jabatan', // Alias for position (backward compatibility)
        'status',
        'is_active', // Alias for status (backward compatibility)
        'role',
        'email_verified_at',
        'password',
        'login_attempts',
        'locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get user initials (2 characters max)
     */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->name);
        if (empty($name)) {
            return 'U'; // Default for Unknown
        }

        // Split name by spaces and get first letter of first two parts
        $words = explode(' ', $name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(mb_substr($word, 0, 1));
            }
        }

        return empty($initials) ? 'U' : substr($initials, 0, 2);
    }

    /**
     * Get user initials with styling for frontend display
     */
    public function getFormattedInitialsAttribute(): array
    {
        $initials = $this->initials;

        // Generate background color based on name hash for consistency
        $colors = [
            'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500',
            'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500',
            'bg-orange-500', 'bg-cyan-500', 'bg-emerald-500', 'bg-lime-500'
        ];

        $colorIndex = crc32($this->name) % count($colors);
        $backgroundColor = $colors[$colorIndex];

        return [
            'text' => $initials,
            'background_color' => $backgroundColor,
            'text_color' => 'text-white',
        ];
    }

    
    /**
     * Get formatted status with badge styling.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => '<span class="badge badge-success">Active</span>',
            'inactive' => '<span class="badge badge-secondary">Inactive</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Get user's full display name with NIP.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->nip})";
    }

    /**
     * Get department badge with styling.
     */
    public function getDepartmentBadgeAttribute(): string
    {
        if (!$this->department) {
            return '<span class="badge badge-light">No Department</span>';
        }

        return '<span class="badge badge-info">' . htmlspecialchars($this->department) . '</span>';
    }

    /**
     * Check if user account is currently locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Check if user is a regular employee (pegawai).
     */
    public function isPegawai(): bool
    {
        return $this->getUserRole() === 'user';
    }

    /**
     * Check if user is an admin helpdesk.
     */
    public function isAdminHelpdesk(): bool
    {
        return $this->getUserRole() === 'admin_helpdesk';
    }

    /**
     * Check if user is an admin aplikasi.
     */
    public function isAdminAplikasi(): bool
    {
        return $this->getUserRole() === 'admin_aplikasi';
    }

    /**
     * Check if user is a teknisi.
     */
    public function isTeknisi(): bool
    {
        return $this->getUserRole() === 'teknisi';
    }

    /**
     * Get user role from database field.
     */
    public function getUserRole(): string
    {
        // Use role field from database if available
        if (isset($this->role) && !empty($this->role)) {
            return $this->role;
        }

        // Fallback to AuthService for backward compatibility
        $authService = app(AuthService::class);
        return $authService->getUserRole($this);
    }

    /**
     * Get user permissions using AuthService.
     */
    public function getUserPermissions(): array
    {
        // Use role field directly for better performance
        if (isset($this->role) && !empty($this->role)) {
            $authService = app(AuthService::class);
            return $authService->getUserPermissions($this);
        }

        // Fallback to AuthService for backward compatibility
        $authService = app(AuthService::class);
        return $authService->getUserPermissions($this);
    }

    /**
     * Check if user has specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Use role field directly for better performance
        if (isset($this->role) && !empty($this->role)) {
            $authService = app(AuthService::class);
            return $authService->hasPermission($this, $permission);
        }

        // Fallback to AuthService for backward compatibility
        $authService = app(AuthService::class);
        return $authService->hasPermission($this, $permission);
    }

    /**
     * Check if user can create tickets.
     */
    public function canCreateTickets(): bool
    {
        return $this->hasPermission('create_tickets');
    }

    /**
     * Check if user can view tickets.
     */
    public function canViewTickets(): bool
    {
        return $this->hasPermission('view_own_tickets') ||
               $this->hasPermission('view_assigned_tickets') ||
               $this->hasPermission('manage_tickets');
    }

    /**
     * Check if user can manage tickets (admin helpdesk only).
     */
    public function canManageTickets(): bool
    {
        return $this->hasPermission('manage_tickets');
    }

    /**
     * Check if user can assign tickets.
     */
    public function canAssignTickets(): bool
    {
        return $this->hasPermission('assign_tickets');
    }

    /**
     * Check if user can manage applications.
     */
    public function canManageApplications(): bool
    {
        return $this->hasPermission('manage_applications');
    }

    /**
     * Check if user can view reports.
     */
    public function canViewReports(): bool
    {
        return $this->hasPermission('view_reports');
    }

    /**
     * Get user's tickets.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_nip', 'nip');
    }

    /**
     * Get notifications for this user using polymorphic relationship.
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get unread notifications count.
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->notifications()->where('status', 'unread')->count();
    }

    /**
     * Get recent tickets (last 30 days).
     */
    public function getRecentTicketsAttribute()
    {
        return $this->tickets()
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get active tickets count.
     */
    public function getActiveTicketsCountAttribute(): int
    {
        return $this->tickets()
            ->whereIn('status', ['open', 'in_progress', 'pending'])
            ->count();
    }

    /**
     * Get resolved tickets count (last 30 days).
     */
    public function getResolvedTicketsCountAttribute(): int
    {
        return $this->tickets()
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', Carbon::now()->subDays(30))
            ->count();
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => Carbon::now()]);
    }

    /**
     * Get formatted last login time.
     */
    public function getFormattedLastLoginAttribute(): string
    {
        if (!$this->last_login_at) {
            return 'Never';
        }

        return $this->last_login_at->diffForHumans();
    }

    /**
     * Get account status information.
     */
    public function getAccountStatusInfoAttribute(): array
    {
        return [
            'status' => $this->status,
            'is_locked' => $this->isLocked(),
            'locked_until' => $this->locked_until,
            'last_login' => $this->last_login_at,
            'login_attempts' => $this->login_attempts ?? 0,
        ];
    }

    /**
     * Get user statistics for dashboard.
     */
    public function getDashboardStatsAttribute(): array
    {
        return [
            'total_tickets' => $this->tickets()->count(),
            'active_tickets' => $this->active_tickets_count,
            'resolved_tickets' => $this->resolved_tickets_count,
            'unread_notifications' => $this->unread_notifications_count,
            'recent_tickets' => $this->recent_tickets,
        ];
    }

    /**
     * Scope for active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for users by department.
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope for users with recent activity.
     */
    public function scopeWithRecentActivity($query, int $days = 30)
    {
        return $query->where('last_login_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Mutator for nama_lengkap (maps to name field)
     */
    public function setNamaLengkapAttribute($value)
    {
        $this->attributes['name'] = $value;
    }

    /**
     * Accessor for nama_lengkap (maps from name field)
     */
    public function getNamaLengkapAttribute()
    {
        return $this->attributes['name'] ?? null;
    }

    /**
     * Mutator for jabatan (maps to position field)
     */
    public function setJabatanAttribute($value)
    {
        $this->attributes['position'] = $value;
    }

    /**
     * Accessor for jabatan (maps from position field)
     */
    public function getJabatanAttribute()
    {
        return $this->attributes['position'] ?? null;
    }

    /**
     * Mutator for unit_kerja (maps to department field)
     */
    public function setUnitKerjaAttribute($value)
    {
        $this->attributes['department'] = $value;
    }

    /**
     * Accessor for unit_kerja (maps from department field)
     */
    public function getUnitKerjaAttribute()
    {
        return $this->attributes['department'] ?? null;
    }

    /**
     * Mutator for no_telepon (maps to phone field)
     */
    public function setNoTeleponAttribute($value)
    {
        $this->attributes['phone'] = $value;
    }

    /**
     * Accessor for no_telepon (maps from phone field)
     */
    public function getNoTeleponAttribute()
    {
        return $this->attributes['phone'] ?? null;
    }

    /**
     * Mutator for is_active (maps to status field)
     */
    public function setIsActiveAttribute($value)
    {
        $this->attributes['status'] = $value ? 'active' : 'inactive';
    }

    /**
     * Accessor for is_active (maps from status field)
     */
    public function getIsActiveAttribute()
    {
        return $this->attributes['status'] === 'active';
    }
}
