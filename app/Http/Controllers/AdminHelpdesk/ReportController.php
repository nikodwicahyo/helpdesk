<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\ScheduledReport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Display the reporting dashboard for AdminHelpdesk users.
     */
    public function index(Request $request)
    {
        // Check if user has report viewing permissions
        if (!$this->canViewReports()) {
            abort(403, 'Unauthorized access to reports');
        }

        // Get date range for filtering
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Get available filters
        $filters = $this->getReportFilters();

        // Get scheduled reports
        $scheduledReports = ScheduledReport::where('created_by', Auth::guard('admin_helpdesk')->id())
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'name' => $report->title,
                    'schedule' => ucfirst($report->schedule_frequency) . ' at ' . $report->formatted_schedule_time,
                    'next_run' => $report->next_run_at ? $report->next_run_at->format('M d, Y H:i') : null,
                    'created_at' => $report->created_at,
                ];
            });

        return Inertia::render('AdminHelpdesk/Reports', [
            'filters' => $filters,
            'dateRange' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'scheduledReports' => $scheduledReports,
        ]);
    }

    /**
     * Generate daily report data.
     */
    public function daily(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $reportData = $this->generateDailyReport($date);

        return response()->json($reportData);
    }

    /**
     * Generate weekly report data.
     */
    public function weekly(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $reportData = $this->generateWeeklyReport($startDate);

        return response()->json($reportData);
    }

    /**
     * Generate monthly report data.
     */
    public function monthly(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        $reportData = $this->generateMonthlyReport($year, $month);

        return response()->json($reportData);
    }

    /**
     * Preview report data based on filters.
     */
    public function preview(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403, 'Unauthorized access to reports');
        }

        $request->validate([
            'type' => 'required|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'status' => 'nullable|in:open,in_progress,waiting_response,resolved,closed',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'aplikasi_id' => 'nullable|exists:aplikasis,id',
        ]);

        $startDate = Carbon::parse($request->get('date_from'))->startOfDay();
        $endDate = Carbon::parse($request->get('date_to'))->endOfDay();

        // Build base query
        $query = Ticket::whereBetween('created_at', [$startDate, $endDate]);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        if ($request->filled('aplikasi_id')) {
            $query->where('aplikasi_id', $request->get('aplikasi_id'));
        }

        // Get tickets with relationships
        $tickets = $query->with(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        // Generate report data based on type
        $reportData = $this->generateReportData($request->get('type'), $tickets, $startDate, $endDate);

        // Add type information for frontend processing
        $reportData['type'] = $request->get('type');

        return response()->json($reportData);
    }

    /**
     * Export report based on format and filters.
     */
    public function export(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403, 'Unauthorized access to reports');
        }

        $request->validate([
            'type' => 'required|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'required|in:excel,pdf',
            'status' => 'nullable|in:open,in_progress,waiting_response,resolved,closed',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'aplikasi_id' => 'nullable|exists:aplikasis,id',
        ]);

        $startDate = Carbon::parse($request->get('date_from'))->startOfDay();
        $endDate = Carbon::parse($request->get('date_to'))->endOfDay();

        // Build query and get data
        $query = Ticket::whereBetween('created_at', [$startDate, $endDate]);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        if ($request->filled('aplikasi_id')) {
            $query->where('aplikasi_id', $request->get('aplikasi_id'));
        }

        $tickets = $query->with(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        $reportData = $this->generateReportData($request->get('type'), $tickets, $startDate, $endDate);

        // Add type information to report data for export processing
        $reportData['type'] = $request->get('type');

        $format = $request->get('format');
        $filename = "{$request->get('type')}_report_" . $startDate->format('Y-m-d') . "_to_" . $endDate->format('Y-m-d');

        if ($format === 'pdf') {
            try {
                Log::info('PDF export attempt', [
                    'type' => $request->get('type'),
                    'filename' => $filename,
                    'has_report_data' => isset($reportData),
                    'has_summary' => isset($reportData['summary']),
                ]);

                $pdf = Pdf::loadView('reports.export', [
                    'reportData' => $reportData ?? [],
                    'type' => $request->get('type'),
                    'filters' => $request->all(),
                ])->setPaper('a4', 'landscape');

                Log::info('PDF created successfully', ['filename' => $filename]);
                return $pdf->download("{$filename}.pdf");
            } catch (\Exception $e) {
                Log::error('PDF generation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'type' => $request->get('type'),
                    'filename' => $filename,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'PDF generation failed: ' . $e->getMessage(),
                    'error_code' => 'PDF_GENERATION_FAILED'
                ], 500);
            }
        }

        // For Excel export, use the DynamicReportExport class
        try {
            return Excel::download(
                new \App\Exports\DynamicReportExport($reportData),
                "{$filename}.xlsx"
            );
        } catch (\Exception $e) {
            Log::error('Excel export failed', [
                'error' => $e->getMessage(),
                'type' => $request->get('type'),
                'format' => $format,
                'filename' => $filename,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
                'error_code' => 'EXPORT_FAILED'
            ], 500);
        }
    }

    /**
     * Generate custom report data with filters.
     */
    public function custom(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'aplikasi_id' => 'nullable|exists:aplikasis,id',
            'kategori_masalah_id' => 'nullable|exists:kategori_masalahs,id',
            'teknisi_nip' => 'nullable|exists:teknisis,nip',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:open,in_progress,waiting_response,resolved,closed',
        ]);

        $reportData = $this->generateCustomReport($request);

        return response()->json($reportData);
    }

    /**
     * Export daily report to Excel.
     */
    public function exportDailyExcel(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $reportData = $this->generateDailyReport($date);

        return Excel::download(
            new \App\Exports\DailyReportExport($reportData),
            "daily-report-{$date}.xlsx"
        );
    }

    /**
     * Export weekly report to Excel.
     */
    public function exportWeeklyExcel(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $reportData = $this->generateWeeklyReport($startDate);

        return Excel::download(
            new \App\Exports\WeeklyReportExport($reportData),
            "weekly-report-{$startDate}.xlsx"
        );
    }

    /**
     * Export monthly report to Excel.
     */
    public function exportMonthlyExcel(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        $reportData = $this->generateMonthlyReport($year, $month);

        return Excel::download(
            new \App\Exports\MonthlyReportExport($reportData),
            "monthly-report-{$year}-{$month}.xlsx"
        );
    }

    /**
     * Export custom report to Excel.
     */
    public function exportCustomExcel(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $reportData = $this->generateCustomReport($request);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        return Excel::download(
            new \App\Exports\CustomReportExport($reportData),
            "custom-report-{$startDate}-to-{$endDate}.xlsx"
        );
    }

    /**
     * Export daily report to PDF.
     */
    public function exportDailyPdf(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $reportData = $this->generateDailyReport($date);

        $pdf = Pdf::loadView('reports.daily', [
            'reportData' => $reportData,
            'date' => $date,
        ]);

        return $pdf->download("daily-report-{$date}.pdf");
    }

    /**
     * Export weekly report to PDF.
     */
    public function exportWeeklyPdf(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfWeek()->format('Y-m-d'));
        $reportData = $this->generateWeeklyReport($startDate);

        $pdf = Pdf::loadView('reports.weekly', [
            'reportData' => $reportData,
            'startDate' => $startDate,
        ]);

        return $pdf->download("weekly-report-{$startDate}.pdf");
    }

    /**
     * Export monthly report to PDF.
     */
    public function exportMonthlyPdf(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);
        $reportData = $this->generateMonthlyReport($year, $month);

        $pdf = Pdf::loadView('reports.monthly', [
            'reportData' => $reportData,
            'year' => $year,
            'month' => $month,
        ]);

        return $pdf->download("monthly-report-{$year}-{$month}.pdf");
    }

    /**
     * Export custom report to PDF.
     */
    public function exportCustomPdf(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $reportData = $this->generateCustomReport($request);

        $pdf = Pdf::loadView('reports.custom', [
            'reportData' => $reportData,
            'filters' => $request->all(),
        ]);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        return $pdf->download("custom-report-{$startDate}-to-{$endDate}.pdf");
    }
    /**
     * Schedule a report for automated generation.
     */
    public function schedule(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403, 'Unauthorized access to schedule reports');
        }

        $request->validate([
            'report_type' => 'required|string|in:performance,usage,sla,compliance,ticket_analysis,user_activity,teknisi_performance,application_health,system_overview,custom',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'schedule_frequency' => 'required|string|in:daily,weekly,monthly',
            'schedule_time' => 'required|date_format:H:i',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'parameters' => 'nullable|array',
            'filters' => 'nullable|array',
        ]);

        try {
            // Calculate next run time based on frequency
            $nextRunAt = $this->calculateNextRunTime($request->schedule_frequency, $request->schedule_time);

            $report = ScheduledReport::create([
                'report_type' => $request->report_type,
                'title' => $request->title,
                'description' => $request->description,
                'created_by' => Auth::guard('admin_helpdesk')->id(),
                'schedule_frequency' => $request->schedule_frequency,
                'schedule_time' => $request->schedule_time,
                'next_run_at' => $nextRunAt,
                'recipients' => $request->recipients,
                'parameters' => $request->parameters ?? [],
                'filters' => $request->filters ?? [],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report scheduled successfully',
                'report' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'next_run' => $report->next_run_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to schedule report', [
                'error' => $e->getMessage(),
                'user' => Auth::user()->nip,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a scheduled report.
     */
    public function deleteSchedule(Request $request, $scheduleId)
    {
        if (!$this->canViewReports()) {
            abort(403, 'Unauthorized access to delete scheduled reports');
        }

        try {
            $report = ScheduledReport::where('id', $scheduleId)
                ->where('created_by', Auth::guard('admin_helpdesk')->id())
                ->firstOrFail();

            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scheduled report deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete scheduled report', [
                'error' => $e->getMessage(),
                'schedule_id' => $scheduleId,
                'user' => Auth::guard('admin_helpdesk')->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scheduled report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate next run time based on frequency and time.
     */
    private function calculateNextRunTime(string $frequency, string $time): Carbon
    {
        $now = Carbon::now();
        $scheduleTime = Carbon::createFromFormat('H:i', $time);

        switch ($frequency) {
            case 'daily':
                $nextRun = $now->copy()->setTimeFrom($scheduleTime);
                if ($nextRun->isPast()) {
                    $nextRun->addDay();
                }
                break;

            case 'weekly':
                $nextRun = $now->copy()->next(Carbon::MONDAY)->setTimeFrom($scheduleTime);
                if ($nextRun->isPast()) {
                    $nextRun->addWeek();
                }
                break;

            case 'monthly':
                $nextRun = $now->copy()->firstOfMonth()->setTimeFrom($scheduleTime);
                if ($nextRun->isPast()) {
                    $nextRun->addMonth();
                }
                break;

            default:
                $nextRun = $now->copy()->addDay()->setTimeFrom($scheduleTime);
        }

        return $nextRun;
    }

    /**
     * Check if current user can view reports.
     */
    private function canViewReports(): bool
    {
        // Check authentication using Auth facade directly
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Allow AdminHelpdesk and AdminAplikasi users
        if (!$user instanceof AdminHelpdesk && !$user instanceof AdminAplikasi) {
            return false;
        }

        // For AdminHelpdesk users, they should have at least one of these permissions
        if ($user instanceof AdminHelpdesk) {
            $permissions = $user->permissions ?? [];
            
            // Check for specific report permissions
            $hasReportPermissions = in_array('generate_reports', $permissions) ||
                                   in_array('view_all_tickets', $permissions) ||
                                   in_array('system_admin', $permissions) ||
                                   in_array('report_viewer', $permissions) ||
                                   in_array('view_reports', $permissions) ||
                                   $user->isSystemAdmin();
            
            // If they don't have specific permissions but are active, grant access by default
            // This ensures that all admin helpdesk users can access reports by default
            if (!$hasReportPermissions) {
                // By default, all active admin helpdesk users can access reports
                return $user->status === 'active';
            }
            
            return $hasReportPermissions;
        }

        // For AdminAplikasi users, use their built-in method
        return $user->canViewReports();
    }

    /**
     * Get available filters for reports.
     */
    private function getReportFilters(): array
    {
        return [
            'applications' => Aplikasi::active()
                ->select('id', 'name', 'code')
                ->get()
                ->toArray(),
            'categories' => KategoriMasalah::select('id', 'name')
                ->get()
                ->toArray(),
            'teknisi' => Teknisi::active()
                ->select('nip', 'name', 'department')
                ->get()
                ->toArray(),
            'priorities' => [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
                ['value' => 'urgent', 'label' => 'Urgent'],
            ],
            'statuses' => [
                ['value' => 'open', 'label' => 'Open'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'waiting_response', 'label' => 'Waiting Response'],
                ['value' => 'resolved', 'label' => 'Resolved'],
                ['value' => 'closed', 'label' => 'Closed'],
            ],
        ];
    }

    /**
     * Generate daily report data.
     */
    private function generateDailyReport(string $date): array
    {
        $date = Carbon::parse($date);
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Base query for the day
        $ticketsQuery = Ticket::whereBetween('created_at', [$startOfDay, $endOfDay]);

        return [
            'date' => $date->format('Y-m-d'),
            'summary' => [
                'total_tickets' => $ticketsQuery->count(),
                'resolved_tickets' => (clone $ticketsQuery)->where('status', Ticket::STATUS_RESOLVED)->count(),
                'avg_resolution_time' => $this->calculateAverageResolutionTime($startOfDay, $endOfDay),
                'escalation_rate' => $this->calculateEscalationRate($startOfDay, $endOfDay),
            ],
            'status_distribution' => $this->getStatusDistribution($startOfDay, $endOfDay),
            'priority_distribution' => $this->getPriorityDistribution($startOfDay, $endOfDay),
            'application_breakdown' => $this->getApplicationBreakdown($startOfDay, $endOfDay),
            'category_breakdown' => $this->getCategoryBreakdown($startOfDay, $endOfDay),
            'teknisi_performance' => $this->getTeknisiPerformance($startOfDay, $endOfDay),
            'sla_compliance' => $this->getSlaCompliance($startOfDay, $endOfDay),
        ];
    }

    /**
     * Generate weekly report data.
     */
    private function generateWeeklyReport(string $startDate): array
    {
        $startDate = Carbon::parse($startDate);
        $endDate = $startDate->copy()->endOfWeek();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'trends' => $this->getWeeklyTrends($startDate, $endDate),
            'top_applications' => $this->getTopApplications($startDate, $endDate, 5),
            'teknisi_performance' => $this->getTeknisiPerformance($startDate, $endDate),
            'escalation_analysis' => $this->getEscalationAnalysis($startDate, $endDate),
            'resolution_efficiency' => $this->getResolutionEfficiency($startDate, $endDate),
        ];
    }

    /**
     * Generate monthly report data.
     */
    private function generateMonthlyReport(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'month_name' => $startDate->format('F Y'),
            ],
            'comparisons' => $this->getMonthOverMonthComparison($year, $month),
            'application_performance' => $this->getApplicationPerformance($startDate, $endDate),
            'category_analysis' => $this->getCategoryAnalysis($startDate, $endDate),
            'best_teknisi' => $this->getBestTeknisi($startDate, $endDate),
            'user_satisfaction' => $this->getUserSatisfaction($startDate, $endDate),
        ];
    }

    /**
     * Generate custom report data with filters.
     */
    private function generateCustomReport(Request $request): array
    {
        $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->get('end_date'))->endOfDay();

        $query = Ticket::whereBetween('created_at', [$startDate, $endDate]);

        // Apply filters
        if ($request->filled('aplikasi_id')) {
            $query->where('aplikasi_id', $request->get('aplikasi_id'));
        }

        if ($request->filled('kategori_masalah_id')) {
            $query->where('kategori_masalah_id', $request->get('kategori_masalah_id'));
        }

        if ($request->filled('teknisi_nip')) {
            $query->where('assigned_teknisi_nip', $request->get('teknisi_nip'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $tickets = $query->with(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi'])->get();

        return [
            'filters' => $request->all(),
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_tickets' => $tickets->count(),
                'resolved_tickets' => $tickets->where('status', Ticket::STATUS_RESOLVED)->count(),
                'avg_resolution_time' => $this->calculateAverageResolutionTime($startDate, $endDate, $tickets),
                'escalation_rate' => $this->calculateEscalationRate($startDate, $endDate, $tickets),
            ],
            'tickets' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'user' => $ticket->user ? $ticket->user->name : 'Unknown',
                    'aplikasi' => $ticket->aplikasi ? $ticket->aplikasi->name : 'Unknown',
                    'kategori' => $ticket->kategoriMasalah ? $ticket->kategoriMasalah->name : 'Unknown',
                    'teknisi' => $ticket->assignedTeknisi ? $ticket->assignedTeknisi->name : 'Unassigned',
                    'created_at' => $ticket->created_at,
                    'resolved_at' => $ticket->resolved_at,
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate report data based on type.
     */
    private function generateReportData(string $type, $tickets, Carbon $startDate, Carbon $endDate): array
    {
        // Base data for all report types
        $baseSummary = [
            'total_tickets' => $tickets->count(),
            'open_tickets' => $tickets->where('status', Ticket::STATUS_OPEN)->count(),
            'resolved_tickets' => $tickets->where('status', Ticket::STATUS_RESOLVED)->count(),
            'in_progress_tickets' => $tickets->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($startDate, $endDate, $tickets),
            'escalation_rate' => $this->calculateEscalationRate($startDate, $endDate, $tickets),
        ];

        $baseData = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_records' => $tickets->count(),
            'summary' => $baseSummary,
            'data' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $ticket->status)),
                    'priority' => $ticket->priority,
                    'priority_label' => ucfirst($ticket->priority),
                    'user' => $ticket->user ? [
                        'nama_lengkap' => $ticket->user->nama_lengkap,
                        'name' => $ticket->user->name,
                    ] : null,
                    'aplikasi' => $ticket->aplikasi ? $ticket->aplikasi->name : null,
                    'kategori' => $ticket->kategoriMasalah ? $ticket->kategoriMasalah->name : null,
                    'teknisi' => $ticket->assignedTeknisi ? $ticket->assignedTeknisi->name : null,
                    'created_at' => $ticket->created_at,
                    'resolved_at' => $ticket->resolved_at,
                ];
            })->toArray(),
        ];

        // Add charts data based on include options
        if (request()->get('include.charts', true)) {
            $statusDistribution = $this->getStatusDistribution($startDate, $endDate);
            $priorityDistribution = $this->getPriorityDistribution($startDate, $endDate);
            $trendData = $this->getWeeklyTrends($startDate, $endDate);

            $baseData['charts'] = [
                'status_distribution' => $this->formatChartData($statusDistribution, 'Status Distribution'),
                'priority_distribution' => $this->formatChartData($priorityDistribution, 'Priority Distribution'),
                'trend_data' => $this->formatTrendData($trendData),
            ];
        }

        // Add type-specific data
        switch ($type) {
            case 'tickets':
                // Basic ticket report (already covered by base data)
                break;

            case 'performance':
                $performanceData = $this->getTeknisiPerformance($startDate, $endDate);
                $baseData['performance'] = $performanceData;

                // Add performance-specific summary data
                $baseData['summary']['total_teknisi'] = count($performanceData);
                $baseData['summary']['avg_resolution_time'] = $this->calculateAverageResolutionTime($startDate, $endDate);
                break;

            case 'sla':
                $slaData = $this->getSlaCompliance($startDate, $endDate);
                $baseData['sla_compliance'] = $slaData;

                // Add SLA-specific summary data
                $baseData['summary']['sla_compliance_rate'] = $slaData['compliance_rate'] ?? 0;
                $baseData['summary']['total_resolved'] = $slaData['total_resolved'] ?? 0;
                break;

            case 'application':
                $applicationData = $this->getApplicationBreakdown($startDate, $endDate);
                $baseData['application_breakdown'] = $applicationData;

                // Add application-specific summary data
                $baseData['summary']['total_applications'] = count($applicationData);
                break;

            case 'user_activity':
                $userActivityData = $this->getUserActivityReport($startDate, $endDate);
                $baseData['user_activity'] = $userActivityData;

                // Add user activity-specific summary data
                $baseData['summary']['active_users'] = $userActivityData['total_users_active'] ?? 0;
                break;

            case 'summary':
                $executiveSummaryData = $this->getExecutiveSummary($startDate, $endDate);
                $baseData['executive_summary'] = $executiveSummaryData;

                // Merge executive summary into main summary
                $baseData['summary'] = array_merge($baseData['summary'], $executiveSummaryData['overview'] ?? []);
                break;
        }

        return $baseData;
    }

    // ==================== CHART DATA FORMATTING METHODS ====================

    /**
     * Format data for pie/doughnut charts.
     */
    private function formatChartData(array $data, string $label): array
    {
        $colors = [
            '#4F46E5', // Indigo
            '#10B981', // Green
            '#F59E0B', // Yellow
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#3B82F6', // Blue
            '#EC4899', // Pink
            '#14B8A6', // Teal
            '#F97316', // Orange
            '#6B7280', // Gray
        ];

        $labels = array_keys($data);
        $values = array_values($data);

        return [
            'labels' => array_map(function($label) {
                return ucfirst(str_replace('_', ' ', $label));
            }, $labels),
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($values)),
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ]
            ]
        ];
    }

    /**
     * Format trend data for line charts.
     */
    private function formatTrendData(array $trendData): array
    {
        $dates = array_column($trendData, 'date');
        $createdCounts = array_column($trendData, 'tickets_created');
        $resolvedCounts = array_column($trendData, 'tickets_resolved');

        return [
            'labels' => array_map(function($date) {
                return \Carbon\Carbon::parse($date)->format('M j');
            }, $dates),
            'datasets' => [
                [
                    'label' => 'Tickets Created',
                    'data' => $createdCounts,
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Tickets Resolved',
                    'data' => $resolvedCounts,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ]
            ]
        ];
    }

    // ==================== CALCULATION METHODS ====================

    /**
     * Calculate average resolution time for a period.
     */
    private function calculateAverageResolutionTime(Carbon $startDate, Carbon $endDate, $tickets = null): ?float
    {
        if ($tickets === null) {
            $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->whereNotNull('resolution_time_minutes')
                ->get();
        } else {
            $resolvedTickets = $tickets->where('status', Ticket::STATUS_RESOLVED)
                ->whereNotNull('resolution_time_minutes');
        }

        return $resolvedTickets->count() > 0
            ? round($resolvedTickets->avg('resolution_time_minutes') / 60, 2) // Convert to hours
            : null;
    }

    /**
     * Calculate escalation rate for a period.
     */
    private function calculateEscalationRate(Carbon $startDate, Carbon $endDate, $tickets = null): float
    {
        if ($tickets === null) {
            $totalTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->count();
            $escalatedTickets = Ticket::where('is_escalated', true)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
        } else {
            $totalTickets = $tickets->count();
            $escalatedTickets = $tickets->where('is_escalated', true)->count();
        }

        return $totalTickets > 0 ? round(($escalatedTickets / $totalTickets) * 100, 2) : 0;
    }

    /**
     * Get status distribution for a period.
     */
    private function getStatusDistribution(Carbon $startDate, Carbon $endDate): array
    {
        return Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get priority distribution for a period.
     */
    private function getPriorityDistribution(Carbon $startDate, Carbon $endDate): array
    {
        return Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority')
            ->toArray();
    }

    /**
     * Get application breakdown for a period.
     */
    private function getApplicationBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        return Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with('aplikasi')
            ->select('aplikasi_id', DB::raw('count(*) as total_tickets'))
            ->whereNotNull('aplikasi_id')
            ->groupBy('aplikasi_id')
            ->get()
            ->map(function ($item) {
                return [
                    'aplikasi_id' => $item->aplikasi_id,
                    'aplikasi' => $item->aplikasi ? $item->aplikasi->name : 'Unknown',
                    'total_tickets' => $item->total_tickets,
                ];
            })
            ->sortByDesc('total_tickets')
            ->values()
            ->toArray();
    }


    /**
     * Get user activity report for a period.
     */
    private function getUserActivityReport(Carbon $startDate, Carbon $endDate): array
    {
        // Get tickets created by users
        $createdTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with('user')
            ->select('user_nip', DB::raw('count(*) as tickets_created'))
            ->whereNotNull('user_nip')
            ->groupBy('user_nip')
            ->get()
            ->keyBy('user_nip');

        // Get resolved tickets by users (users who created the tickets)
        $resolvedTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Ticket::STATUS_RESOLVED)
            ->with('user')
            ->select('user_nip', DB::raw('count(*) as tickets_resolved'))
            ->whereNotNull('user_nip')
            ->groupBy('user_nip')
            ->get()
            ->keyBy('user_nip');

        // Combine the data
        $userActivity = [];
        foreach ($createdTickets as $userNip => $created) {
            $resolved = $resolvedTickets->get($userNip);
            $resolvedCount = $resolved ? $resolved->tickets_resolved : 0;
            $resolutionRate = $created->tickets_created > 0 ? round(($resolvedCount / $created->tickets_created) * 100, 2) : 0;

            $userActivity[] = [
                'user' => $created->user ? [
                    'nama_lengkap' => $created->user->nama_lengkap,
                    'name' => $created->user->name,
                ] : null,
                'tickets_created' => $created->tickets_created,
                'tickets_resolved' => $resolvedCount,
                'resolution_rate' => $resolutionRate,
            ];
        }

        // Sort by tickets created descending
        usort($userActivity, function($a, $b) {
            return $b['tickets_created'] - $a['tickets_created'];
        });

        return [
            'total_users_active' => count($userActivity),
            'user_activity' => $userActivity,
        ];
    }

    /**
     * Get executive summary for a period.
     */
    private function getExecutiveSummary(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->get();
        $resolvedTicketsQuery = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate]);

        $totalTickets = $tickets->count();
        $openTickets = $tickets->where('status', Ticket::STATUS_OPEN)->count();
        $inProgressTickets = $tickets->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        $resolvedTickets = $resolvedTicketsQuery->count();
        $avgResolutionTime = $this->calculateAverageResolutionTime($startDate, $endDate);

        // Calculate resolution rate properly (resolved tickets vs total tickets created in period)
        $resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0;

        // Get top applications by ticket count
        $topApplications = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with('aplikasi')
            ->select('aplikasi_id', DB::raw('count(*) as count'))
            ->whereNotNull('aplikasi_id')
            ->groupBy('aplikasi_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'aplikasi' => $item->aplikasi ? $item->aplikasi->name : 'Unknown',
                    'count' => $item->count,
                ];
            })
            ->toArray();

        // Get priority distribution
        $priorityDistribution = $this->getPriorityDistribution($startDate, $endDate);

        // Get SLA compliance
        $slaCompliance = $this->getSlaCompliance($startDate, $endDate);

        // Get top teknisi performance
        $topTeknisi = collect($this->getTeknisiPerformance($startDate, $endDate))
            ->take(5)
            ->toArray();

        // Get user activity summary
        $userActivity = $this->getUserActivityReport($startDate, $endDate);

        return [
            'overview' => [
                'total_tickets' => $totalTickets,
                'open_tickets' => $openTickets,
                'in_progress_tickets' => $inProgressTickets,
                'resolved_tickets' => $resolvedTickets,
                'avg_resolution_time' => $avgResolutionTime,
                'resolution_rate' => $resolutionRate,
                'sla_compliance_rate' => $slaCompliance['compliance_rate'] ?? 0,
                'active_users' => $userActivity['total_users_active'] ?? 0,
            ],
            'top_applications' => $topApplications,
            'priority_breakdown' => $priorityDistribution,
            'sla_compliance' => $slaCompliance,
            'top_teknisi' => $topTeknisi,
            'user_activity_summary' => [
                'total_active_users' => $userActivity['total_users_active'] ?? 0,
            ],
            'trend_analysis' => [
                'daily_trends' => $this->getWeeklyTrends($startDate, $endDate),
                'monthly_trend' => $this->getMonthlyTrends($startDate, $endDate),
            ],
        ];
    }

    /**
     * Get monthly trends for a period.
     */
    private function getMonthlyTrends(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy('month')
            ->get();

        $trendData = [];
        foreach ($tickets as $ticket) {
            $trendData[] = [
                'month' => $ticket->month,
                'count' => $ticket->count,
            ];
        }

        return $trendData;
    }

    /**
     * Get category breakdown for a period.
     */
    private function getCategoryBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        return Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with('kategoriMasalah')
            ->select('kategori_masalah_id', DB::raw('count(*) as count'))
            ->whereNotNull('kategori_masalah_id')
            ->groupBy('kategori_masalah_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->kategoriMasalah ? $item->kategoriMasalah->name : 'Unknown',
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get teknisi performance for a period.
     */
    private function getTeknisiPerformance(Carbon $startDate, Carbon $endDate): array
    {
        return Teknisi::select('nip', 'name')
            ->withCount([
                'assignedTickets as resolved_tickets_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', Ticket::STATUS_RESOLVED)
                          ->whereBetween('resolved_at', [$startDate, $endDate]);
                }
            ])
            ->withCount([
                'assignedTickets as total_assigned_tickets_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ])
            ->having('total_assigned_tickets_count', '>', 0)
            ->orderBy('resolved_tickets_count', 'desc')
            ->get()
            ->map(function ($teknisi) {
                return [
                    'nip' => $teknisi->nip,
                    'name' => $teknisi->name,
                    'resolved_tickets' => $teknisi->resolved_tickets_count,
                    'total_assigned' => $teknisi->total_assigned_tickets_count,
                    'resolution_rate' => $teknisi->total_assigned_tickets_count > 0
                        ? round(($teknisi->resolved_tickets_count / $teknisi->total_assigned_tickets_count) * 100, 1)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get SLA compliance data for a period.
     */
    private function getSlaCompliance(Carbon $startDate, Carbon $endDate): array
    {
        $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('resolved_at')
            ->whereNotNull('resolution_time_minutes')
            ->get();

        $totalResolved = $resolvedTickets->count();

        // Calculate within SLA based on priority and resolution time
        $withinSla = 0;
        foreach ($resolvedTickets as $ticket) {
            $slaHours = Ticket::PRIORITY_SLA_HOURS[$ticket->priority] ?? 48; // Default to 48 hours
            $slaMinutes = $slaHours * 60;

            if ($ticket->resolution_time_minutes <= $slaMinutes) {
                $withinSla++;
            }
        }

        return [
            'total_resolved' => $totalResolved,
            'within_sla' => $withinSla,
            'sla_breached' => $totalResolved - $withinSla,
            'compliance_rate' => $totalResolved > 0 ? round(($withinSla / $totalResolved) * 100, 1) : 0,
            'priority_breakdown' => $this->getSlaByPriority($startDate, $endDate),
        ];
    }

    /**
     * Get SLA breakdown by priority.
     */
    private function getSlaByPriority(Carbon $startDate, Carbon $endDate): array
    {
        $breakdown = [];

        foreach (Ticket::PRIORITY_SLA_HOURS as $priority => $slaHours) {
            $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->where('priority', $priority)
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->whereNotNull('resolution_time_minutes')
                ->get();

            $total = $resolvedTickets->count();
            $slaMinutes = $slaHours * 60;

            $withinSla = $resolvedTickets->filter(function ($ticket) use ($slaMinutes) {
                return $ticket->resolution_time_minutes <= $slaMinutes;
            })->count();

            $breakdown[ucfirst($priority)] = [
                'total' => $total,
                'within_sla' => $withinSla,
                'sla_breached' => $total - $withinSla,
                'compliance_rate' => $total > 0 ? round(($withinSla / $total) * 100, 1) : 0,
                'sla_hours' => $slaHours,
            ];
        }

        return $breakdown;
    }

    /**
     * Get weekly trends data.
     */
    private function getWeeklyTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'tickets_created' => Ticket::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'tickets_resolved' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [$dayStart, $dayEnd])->count(),
            ];

            $currentDate->addDay();
        }

        return $trends;
    }

    /**
     * Get top applications for a period.
     */
    private function getTopApplications(Carbon $startDate, Carbon $endDate, int $limit = 5): array
    {
        return Aplikasi::select('id', 'name', 'code')
            ->withCount([
                'tickets as total_tickets' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ])
            ->having('total_tickets', '>', 0)
            ->orderBy('total_tickets', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($aplikasi) {
                return [
                    'id' => $aplikasi->id,
                    'name' => $aplikasi->name,
                    'code' => $aplikasi->code,
                    'total_tickets' => $aplikasi->total_tickets,
                ];
            })
            ->toArray();
    }

    /**
     * Get escalation analysis for a period.
     */
    private function getEscalationAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $escalatedTickets = Ticket::where('is_escalated', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['aplikasi', 'kategoriMasalah'])
            ->get();

        return [
            'total_escalated' => $escalatedTickets->count(),
            'escalation_reasons' => $escalatedTickets->groupBy('escalation_reason')
                ->map(function ($tickets) {
                    return $tickets->count();
                })
                ->toArray(),
            'escalated_by_application' => $escalatedTickets->groupBy('aplikasi.name')
                ->map(function ($tickets) {
                    return $tickets->count();
                })
                ->toArray(),
        ];
    }

    /**
     * Get resolution efficiency metrics.
     */
    private function getResolutionEfficiency(Carbon $startDate, Carbon $endDate): array
    {
        $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('resolution_time_minutes')
            ->get();

        return [
            'total_resolved' => $resolvedTickets->count(),
            'avg_resolution_time_hours' => $resolvedTickets->count() > 0
                ? round($resolvedTickets->avg('resolution_time_minutes') / 60, 2)
                : 0,
            'fastest_resolution' => $resolvedTickets->count() > 0
                ? round($resolvedTickets->min('resolution_time_minutes') / 60, 2)
                : 0,
            'slowest_resolution' => $resolvedTickets->count() > 0
                ? round($resolvedTickets->max('resolution_time_minutes') / 60, 2)
                : 0,
        ];
    }

    /**
     * Get month-over-month comparison.
     */
    private function getMonthOverMonthComparison(int $year, int $month): array
    {
        $currentMonth = Carbon::create($year, $month, 1);
        $previousMonth = $currentMonth->copy()->subMonth();

        $currentData = $this->getMonthlyMetrics($currentMonth);
        $previousData = $this->getMonthlyMetrics($previousMonth);

        return [
            'current_month' => $currentData,
            'previous_month' => $previousData,
            'growth' => $this->calculateGrowth($currentData, $previousData),
        ];
    }

    /**
     * Get monthly metrics for a given month.
     */
    private function getMonthlyMetrics(Carbon $month): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        return [
            'tickets_created' => Ticket::whereBetween('created_at', [$startDate, $endDate])->count(),
            'tickets_resolved' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereBetween('resolved_at', [$startDate, $endDate])->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($startDate, $endDate),
        ];
    }

    /**
     * Calculate growth between two periods.
     */
    private function calculateGrowth(array $current, array $previous): array
    {
        $growth = [];

        foreach ($current as $key => $value) {
            if (isset($previous[$key]) && $previous[$key] > 0) {
                $growth[$key] = round((($value - $previous[$key]) / $previous[$key]) * 100, 1);
            } else {
                $growth[$key] = $value > 0 ? 100 : 0;
            }
        }

        return $growth;
    }

    /**
     * Get application performance metrics.
     */
    private function getApplicationPerformance(Carbon $startDate, Carbon $endDate): array
    {
        return Aplikasi::select('id', 'name', 'code')
            ->withCount([
                'tickets as total_tickets' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ])
            ->withCount([
                'tickets as resolved_tickets' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', Ticket::STATUS_RESOLVED)
                          ->whereBetween('resolved_at', [$startDate, $endDate]);
                }
            ])
            ->having('total_tickets', '>', 0)
            ->get()
            ->map(function ($aplikasi) {
                return [
                    'id' => $aplikasi->id,
                    'name' => $aplikasi->name,
                    'code' => $aplikasi->code,
                    'total_tickets' => $aplikasi->total_tickets,
                    'resolved_tickets' => $aplikasi->resolved_tickets,
                    'resolution_rate' => $aplikasi->total_tickets > 0
                        ? round(($aplikasi->resolved_tickets / $aplikasi->total_tickets) * 100, 1)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get category analysis for a period.
     */
    private function getCategoryAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        return KategoriMasalah::select('id', 'name')
            ->withCount([
                'tickets as total_tickets' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ])
            ->withCount([
                'tickets as resolved_tickets' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', Ticket::STATUS_RESOLVED)
                          ->whereBetween('resolved_at', [$startDate, $endDate]);
                }
            ])
            ->having('total_tickets', '>', 0)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_tickets' => $category->total_tickets,
                    'resolved_tickets' => $category->resolved_tickets,
                    'resolution_rate' => $category->total_tickets > 0
                        ? round(($category->resolved_tickets / $category->total_tickets) * 100, 1)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get best teknisi for a period.
     */
    private function getBestTeknisi(Carbon $startDate, Carbon $endDate): array
    {
        return Teknisi::select('nip', 'name', 'department')
            ->withCount([
                'assignedTickets as resolved_tickets_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', Ticket::STATUS_RESOLVED)
                          ->whereBetween('resolved_at', [$startDate, $endDate]);
                }
            ])
            ->withCount([
                'assignedTickets as total_assigned_tickets_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ])
            ->having('resolved_tickets_count', '>', 0)
            ->orderBy('resolved_tickets_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($teknisi) {
                return [
                    'nip' => $teknisi->nip,
                    'name' => $teknisi->name,
                    'department' => $teknisi->department,
                    'resolved_tickets' => $teknisi->resolved_tickets_count,
                    'total_assigned' => $teknisi->total_assigned_tickets_count,
                    'resolution_rate' => $teknisi->total_assigned_tickets_count > 0
                        ? round(($teknisi->resolved_tickets_count / $teknisi->total_assigned_tickets_count) * 100, 1)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get user satisfaction metrics.
     */
    private function getUserSatisfaction(Carbon $startDate, Carbon $endDate): array
    {
        $ratedTickets = Ticket::whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('user_rating')
            ->get();

        return [
            'total_rated' => $ratedTickets->count(),
            'average_rating' => $ratedTickets->count() > 0 ? round($ratedTickets->avg('user_rating'), 1) : 0,
            'rating_distribution' => $ratedTickets->count() > 0
                ? $ratedTickets->groupBy('user_rating')->map(function ($tickets) use ($ratedTickets) {
                    return round(($tickets->count() / $ratedTickets->count()) * 100, 1);
                })->toArray()
                : [],
        ];
    }
}