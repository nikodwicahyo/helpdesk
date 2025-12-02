<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    /**
     * Display the activity log page.
     */
    public function index(Request $request)
    {
        $admin = Auth::user();

        // Get filters from request
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'user_id' => $request->input('user_id'),
            'action' => $request->input('action'),
            'entity_type' => $request->input('entity_type'),
            'search' => $request->input('search'),
        ];

        // Build query
        $query = AuditLog::query()->orderBy('created_at', 'desc');

        // Apply date range filter
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Apply user filter
        if (!empty($filters['user_id'])) {
            $query->where('actor_id', $filters['user_id']);
        }

        // Apply action filter
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Apply entity type filter
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', 'like', '%' . $filters['entity_type'] . '%');
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', "%{$searchTerm}%")
                  ->orWhere('actor_name', 'like', "%{$searchTerm}%")
                  ->orWhere('entity_type', 'like', "%{$searchTerm}%")
                  ->orWhere('action', 'like', "%{$searchTerm}%");
            });
        }

        // Paginate results
        $logs = $query->paginate($request->input('per_page', 50));

        // Get users for filter dropdown
        $users = $this->getUsersForFilter();

        // Get action types for filter dropdown
        $actionTypes = $this->getActionTypes();

        return Inertia::render('AdminHelpdesk/ActivityLog', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => $users,
            'actionTypes' => $actionTypes,
        ]);
    }

    /**
     * Get detailed log entry.
     */
    public function show($id)
    {
        // For now, return a basic response
        // In a full implementation, this would fetch from database or log files
        return response()->json([
            'message' => 'Log details endpoint - to be implemented with AuditLogService'
        ]);
    }

    /**
     * Export logs to CSV or Excel.
     */
    public function export(Request $request)
    {
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'user_id' => $request->input('user_id'),
            'action' => $request->input('action'),
            'entity_type' => $request->input('entity_type'),
            'search' => $request->input('search'),
        ];

        $format = $request->input('format', 'csv');

        try {
            return $this->exportToCsv($filters, $format);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export logs: ' . $e->getMessage());
        }
    }



    /**
     * Get users for filter dropdown.
     */
    private function getUsersForFilter(): array
    {
        $users = [];

        // Get admin helpdesks
        $adminUsers = DB::table('admin_helpdesks')
            ->select('nip as id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        foreach ($adminUsers as $user) {
            $users[] = [
                'id' => $user->id,
                'name' => $user->name,
                'type' => 'Admin Helpdesk',
            ];
        }

        // Get admin aplikasis
        $adminAplikasiUsers = DB::table('admin_aplikasis')
            ->select('nip as id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        foreach ($adminAplikasiUsers as $user) {
            $users[] = [
                'id' => $user->id,
                'name' => $user->name,
                'type' => 'Admin Aplikasi',
            ];
        }

        // Get teknisis
        $teknisiUsers = DB::table('teknisis')
            ->select('nip as id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        foreach ($teknisiUsers as $user) {
            $users[] = [
                'id' => $user->id,
                'name' => $user->name,
                'type' => 'Teknisi',
            ];
        }

        // Get regular users
        $regularUsers = DB::table('users')
            ->select('nip as id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(100) // Limit to avoid too many options
            ->get();

        foreach ($regularUsers as $user) {
            $users[] = [
                'id' => $user->id,
                'name' => $user->name,
                'type' => 'User',
            ];
        }

        return $users;
    }

    /**
     * Get available action types for filter dropdown.
     */
    private function getActionTypes(): array
    {
        return [
            // Basic CRUD
            ['value' => 'created', 'label' => 'Created'],
            ['value' => 'updated', 'label' => 'Updated'],
            ['value' => 'deleted', 'label' => 'Deleted'],
            
            // Ticket Actions
            ['value' => 'assigned', 'label' => 'Assigned'],
            ['value' => 'unassigned', 'label' => 'Unassigned'],
            ['value' => 'reassigned', 'label' => 'Reassigned'],
            ['value' => 'resolved', 'label' => 'Resolved'],
            ['value' => 'closed', 'label' => 'Closed'],
            ['value' => 'reopened', 'label' => 'Reopened'],
            ['value' => 'escalated', 'label' => 'Escalated'],
            ['value' => 'commented', 'label' => 'Commented'],
            ['value' => 'status_changed', 'label' => 'Status Changed'],
            ['value' => 'priority_changed', 'label' => 'Priority Changed'],
            
            // Bulk Actions
            ['value' => 'bulk_assigned', 'label' => 'Bulk Assigned'],
            ['value' => 'bulk_updated', 'label' => 'Bulk Updated'],
            ['value' => 'bulk_deleted', 'label' => 'Bulk Deleted'],
            ['value' => 'bulk_activated', 'label' => 'Bulk Activated'],
            ['value' => 'bulk_deactivated', 'label' => 'Bulk Deactivated'],
            
            // User Actions
            ['value' => 'profile_updated', 'label' => 'Profile Updated'],
            ['value' => 'password_changed', 'label' => 'Password Changed'],
            ['value' => 'email_changed', 'label' => 'Email Changed'],
            ['value' => 'account_locked', 'label' => 'Account Locked'],
            ['value' => 'account_unlocked', 'label' => 'Account Unlocked'],
            
            // Authentication
            ['value' => 'login', 'label' => 'Login'],
            ['value' => 'logout', 'label' => 'Logout'],
            ['value' => 'login_failed', 'label' => 'Login Failed'],
            
            // Data Operations
            ['value' => 'exported', 'label' => 'Data Exported'],
            ['value' => 'imported', 'label' => 'Data Imported'],
            
            // Reports & Settings
            ['value' => 'report_generated', 'label' => 'Report Generated'],
            ['value' => 'setting_changed', 'label' => 'Setting Changed'],
            ['value' => 'config_changed', 'label' => 'Configuration Changed'],
        ];
    }

    /**
     * Export logs to CSV or Excel.
     */
    private function exportToCsv(array $filters = [], string $format = 'csv')
    {
        // Build query with filters
        $query = AuditLog::query()->orderBy('created_at', 'desc');

        // Apply same filters as index page
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('actor_id', $filters['user_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', 'like', '%' . $filters['entity_type'] . '%');
        }
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', "%{$searchTerm}%")
                  ->orWhere('actor_name', 'like', "%{$searchTerm}%")
                  ->orWhere('entity_type', 'like', "%{$searchTerm}%")
                  ->orWhere('action', 'like', "%{$searchTerm}%");
            });
        }

        // Get all matching logs (limit to 10k for performance)
        $logs = $query->limit(10000)->get();

        // Set filename and content type
        if ($format === 'excel') {
            $filename = 'activity_log_' . date('Y-m-d_H-i-s') . '.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $filename = 'activity_log_' . date('Y-m-d_H-i-s') . '.csv';
            $contentType = 'text/csv';
        }

        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // Add CSV header
            fputcsv($file, [
                'Timestamp',
                'Action',
                'Actor Name',
                'Actor Type',
                'Entity Type',
                'Entity ID',
                'Description',
                'IP Address',
                'Route Name',
                'HTTP Method'
            ]);

            // Add data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                    $log->action ?? '',
                    $log->actor_name ?? '',
                    $log->actor_type ?? '',
                    $log->entity_type ?? '',
                    $log->entity_id ?? '',
                    $log->description ?? $log->getDescription(),
                    $log->ip_address ?? '',
                    $log->route_name ?? '',
                    $log->http_method ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}