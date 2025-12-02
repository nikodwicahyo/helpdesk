<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Teknisi;
use App\Models\Ticket;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\User;
use App\Exports\TeknisiReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class TeknisiController extends Controller
{
    /**
     * Display teknisi reports and analytics.
     */
    public function reports(Request $request)
    {
        $user = Auth::user();

        // Ensure user is a teknisi
        if (!$user instanceof Teknisi) {
            abort(403, 'Access denied. Teknisi role required.');
        }

        $teknisi = $user;

        // Get date range for filtering
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        // Get teknisi performance data
        $performanceData = $this->getTeknisiPerformanceData($teknisi, $startDate, $endDate);

        // Get ticket statistics
        $ticketStats = $this->getTicketStatistics($teknisi, $startDate, $endDate);

        // Get application performance
        $applicationPerformance = $this->getApplicationPerformance($teknisi, $startDate, $endDate);

        // Get category performance
        $categoryPerformance = $this->getCategoryPerformance($teknisi, $startDate, $endDate);

        // Get workload trends
        $workloadTrends = $this->getWorkloadTrends($teknisi, $startDate, $endDate);

        // Get recent activity
        $recentActivity = $this->getRecentActivity($teknisi);

        // Get achievements and milestones
        $achievements = $this->getAchievements($teknisi, $startDate, $endDate);

        // Get specializations (application expertise)
        $specializations = $this->getSpecializations($teknisi);

        // Get recent feedback
        $recentFeedback = $this->getRecentFeedback($teknisi);

        // Get performance metrics for the card
        $performance = [
            'resolution_rate' => $performanceData['overview']['resolution_rate'] ?? 0,
            'avg_response_time' => round(($performanceData['overview']['avg_resolution_time_hours'] ?? 0) * 0.3, 1), // Estimated first response
            'avg_resolution_time' => $performanceData['overview']['avg_resolution_time_hours'] ?? 0,
            'tickets_this_week' => $performanceData['trends']['tickets_this_week'] ?? 0,
            'avg_rating' => $performanceData['overview']['customer_satisfaction'] ?? 0,
        ];

        return Inertia::render('Teknisi/Reports', [
            'performanceData' => [
                'totalResolved' => $performanceData['total_resolved'] ?? 0,
                'avgResolutionTime' => isset($performanceData['avg_resolution_time_hours']) 
                    ? number_format($performanceData['avg_resolution_time_hours'], 1) . 'h' 
                    : '0h',
                'satisfactionRate' => $performanceData['satisfaction_rate'] ?? 0,
                'productivityScore' => $performanceData['productivity_score'] ?? 0,
            ],
            'ticketStats' => $ticketStats,
            'applicationPerformance' => $applicationPerformance,
            'categoryPerformance' => $categoryPerformance,
            'workloadTrends' => $workloadTrends,
            'recentActivity' => $recentActivity,
            'achievements' => $achievements,
            'specializations' => $specializations,
            'recentFeedback' => $recentFeedback,
            'performance' => $performance,
            'dateRange' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Export teknisi report.
     */
    public function exportReport(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Teknisi) {
            abort(403, 'Access denied. Teknisi role required.');
        }

        $teknisi = $user;
        $period = $request->get('period', 'month');

        // Calculate date range based on period
        $endDate = Carbon::now();
        switch ($period) {
            case 'week':
                $startDate = Carbon::now()->subWeek();
                break;
            case 'quarter':
                $startDate = Carbon::now()->subQuarter();
                break;
            case 'year':
                $startDate = Carbon::now()->subYear();
                break;
            case 'month':
            default:
                $startDate = Carbon::now()->subMonth();
                break;
        }

        $format = $request->get('format', 'excel');
        $filename = "teknisi-report-{$teknisi->nip}-{$startDate->format('Ymd')}-{$endDate->format('Ymd')}";

        try {
            if ($format === 'excel') {
                return Excel::download(
                    new TeknisiReportExport($teknisi, $startDate, $endDate),
                    $filename . '.xlsx'
                );
            }

            // PDF export
            $performanceData = $this->getTeknisiPerformanceData($teknisi, $startDate, $endDate);
            $ticketStats = $this->getTicketStatistics($teknisi, $startDate, $endDate);
            $achievements = $this->getAchievements($teknisi, $startDate, $endDate);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.teknisi-report', [
                'teknisi' => $teknisi,
                'performanceData' => $performanceData,
                'ticketStats' => $ticketStats,
                'achievements' => $achievements,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'generatedAt' => Carbon::now(),
            ]);

            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error exporting teknisi report', [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->withErrors(['Export failed. Please try again.']);
        }
    }

    /**
     * Get comprehensive teknisi performance data.
     */
    private function getTeknisiPerformanceData(Teknisi $teknisi, Carbon $startDate, Carbon $endDate): array
    {
        $totalTickets = $teknisi->assignedTickets()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $resolvedTickets = $teknisi->resolvedTickets()
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        $avgResolutionTime = $teknisi->resolvedTickets()
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');

        $customerSatisfaction = $teknisi->resolvedTickets()
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('user_rating')
            ->avg('user_rating');

        $resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0;
        $avgResolutionTimeHours = $avgResolutionTime ? round($avgResolutionTime / 60, 2) : 0;
        $satisfactionRate = $customerSatisfaction ? round(($customerSatisfaction / 5) * 100, 1) : 0;
        
        // Calculate productivity score (based on resolution rate, avg time, and satisfaction)
        $productivityScore = round(($resolutionRate * 0.4) + ($satisfactionRate * 0.4) + (min(100, (100 - min($avgResolutionTimeHours * 2, 100))) * 0.2));

        return [
            'total_resolved' => $resolvedTickets,
            'avg_resolution_time_hours' => $avgResolutionTimeHours,
            'satisfaction_rate' => $satisfactionRate,
            'productivity_score' => $productivityScore,
            'overview' => [
                'total_tickets_assigned' => $totalTickets,
                'tickets_resolved' => $resolvedTickets,
                'resolution_rate' => $resolutionRate,
                'avg_resolution_time_hours' => $avgResolutionTimeHours,
                'customer_satisfaction' => $customerSatisfaction ? round($customerSatisfaction, 2) : 0,
                'current_workload' => $teknisi->getCurrentWorkload(),
                'performance_grade' => $teknisi->performance_grade,
            ],
            'trends' => [
                'tickets_this_week' => $teknisi->assignedTickets()
                    ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                    ->count(),
                'resolved_this_week' => $teknisi->resolvedTickets()
                    ->whereBetween('resolved_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                    ->count(),
                'avg_resolution_trend' => $this->calculateResolutionTrend($teknisi, $startDate, $endDate),
            ],
        ];
    }

    /**
     * Get ticket statistics for teknisi.
     */
    private function getTicketStatistics(Teknisi $teknisi, Carbon $startDate, Carbon $endDate): array
    {
        $tickets = $teknisi->assignedTickets()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['aplikasi', 'kategoriMasalah'])
            ->get();

        return [
            'by_status' => $tickets->groupBy('status')->map(function ($items) {
                return $items->count();
            })->toArray(),
            'by_priority' => $tickets->groupBy('priority')->map(function ($items) {
                return $items->count();
            })->toArray(),
            'by_application' => $tickets->groupBy('aplikasi.name')->map(function ($items) {
                return $items->count();
            })->toArray(),
            'by_category' => $tickets->groupBy('kategoriMasalah.name')->map(function ($items) {
                return $items->count();
            })->toArray(),
            'resolution_times' => [
                'fastest' => $tickets->where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->min('resolution_time_minutes'),
                'slowest' => $tickets->where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->max('resolution_time_minutes'),
                'average' => $tickets->where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->avg('resolution_time_minutes'),
            ],
        ];
    }

    /**
     * Get application performance for teknisi.
     */
    private function getApplicationPerformance(Teknisi $teknisi, Carbon $startDate, Carbon $endDate): array
    {
        // Get applications from assigned tickets (fallback method)
        $applicationIds = $teknisi->assignedTickets()
            ->distinct()
            ->pluck('aplikasi_id')
            ->filter();

        $performance = [];

        foreach ($applicationIds as $aplikasiId) {
            $application = Aplikasi::find($aplikasiId);

            if ($application) {
                $appTickets = $teknisi->assignedTickets()
                    ->where('aplikasi_id', $aplikasiId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $resolvedTickets = $appTickets->where('status', Ticket::STATUS_RESOLVED);

                $performance[] = [
                    'application' => [
                        'id' => $application->id,
                        'name' => $application->name,
                        'code' => $application->code,
                    ],
                    'expertise_level' => null, // Placeholder: can be calculated based on performance
                    'total_tickets' => $appTickets->count(),
                    'resolved_tickets' => $resolvedTickets->count(),
                    'resolution_rate' => $appTickets->count() > 0 ? round(($resolvedTickets->count() / $appTickets->count()) * 100, 1) : 0,
                    'avg_resolution_time' => $resolvedTickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
                ];
            }
        }

        return $performance;
    }

    /**
     * Get category performance for teknisi.
     */
    private function getCategoryPerformance(Teknisi $teknisi, Carbon $startDate, Carbon $endDate): array
    {
        // Get categories from assigned tickets (fallback method)
        $categoryIds = $teknisi->assignedTickets()
            ->distinct()
            ->pluck('kategori_masalah_id')
            ->filter();

        $performance = [];

        foreach ($categoryIds as $kategoriId) {
            $category = KategoriMasalah::find($kategoriId);

            if ($category) {
                $catTickets = $teknisi->assignedTickets()
                    ->where('kategori_masalah_id', $kategoriId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $resolvedTickets = $catTickets->where('status', Ticket::STATUS_RESOLVED);
                $resolutionRate = $catTickets->count() > 0 ? round(($resolvedTickets->count() / $catTickets->count()) * 100, 1) : 0;

                $performance[] = [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ],
                    'expertise_level' => null, // Placeholder: can be calculated based on performance
                    'success_rate' => $resolutionRate,
                    'total_tickets' => $catTickets->count(),
                    'resolved_tickets' => $resolvedTickets->count(),
                    'resolution_rate' => $resolutionRate,
                    'avg_resolution_time' => $resolvedTickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
                ];
            }
        }

        return $performance;
    }

    /**
     * Get workload trends for teknisi.
     */
    private function getWorkloadTrends(Teknisi $teknisi, Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $ticketsCreated = $teknisi->assignedTickets()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $ticketsResolved = $teknisi->resolvedTickets()
                ->whereBetween('resolved_at', [$dayStart, $dayEnd])
                ->count();

            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'tickets_assigned' => $ticketsCreated,
                'tickets_resolved' => $ticketsResolved,
                'net_change' => $ticketsCreated - $ticketsResolved,
            ];

            $currentDate->addDay();
        }

        return $trends;
    }

    /**
     * Get recent activity for teknisi.
     */
    private function getRecentActivity(Teknisi $teknisi, int $limit = 10): array
    {
        $activities = [];

        // Recent tickets
        $recentTickets = $teknisi->assignedTickets()
            ->with(['aplikasi', 'kategoriMasalah'])
            ->latest()
            ->limit(5)
            ->get();

        foreach ($recentTickets as $ticket) {
            $activities[] = [
                'type' => 'ticket_assigned',
                'title' => 'Ticket Assigned',
                'description' => "Assigned ticket #{$ticket->ticket_number}: {$ticket->title}",
                'application' => $ticket->aplikasi ? $ticket->aplikasi->name : 'Unknown',
                'date' => $ticket->created_at,
                'formatted_date' => $ticket->created_at->diffForHumans(),
                'icon' => 'ticket',
                'color' => 'blue',
            ];
        }

        // Recent resolutions
        $recentResolutions = $teknisi->resolvedTickets()
            ->with(['aplikasi'])
            ->latest('resolved_at')
            ->limit(5)
            ->get();

        foreach ($recentResolutions as $ticket) {
            $activities[] = [
                'type' => 'ticket_resolved',
                'title' => 'Ticket Resolved',
                'description' => "Resolved ticket #{$ticket->ticket_number}",
                'application' => $ticket->aplikasi ? $ticket->aplikasi->name : 'Unknown',
                'date' => $ticket->resolved_at,
                'formatted_date' => $ticket->resolved_at->diffForHumans(),
                'icon' => 'check-circle',
                'color' => 'green',
            ];
        }

        // Sort by date (newest first)
        usort($activities, function ($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get achievements and milestones for teknisi.
     */
    private function getAchievements(Teknisi $teknisi, Carbon $startDate, Carbon $endDate): array
    {
        $achievements = [];

        // Resolution milestones
        $resolvedCount = $teknisi->resolvedTickets()
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        if ($resolvedCount >= 50) {
            $achievements[] = [
                'type' => 'resolution_milestone',
                'title' => 'Resolution Master',
                'description' => "Resolved {$resolvedCount} tickets this period",
                'icon' => 'trophy',
                'color' => 'gold',
            ];
        } elseif ($resolvedCount >= 25) {
            $achievements[] = [
                'type' => 'resolution_milestone',
                'title' => 'Problem Solver',
                'description' => "Resolved {$resolvedCount} tickets this period",
                'icon' => 'star',
                'color' => 'blue',
            ];
        }

        // Performance achievements
        $resolutionRate = $teknisi->getResolutionRate();
        if ($resolutionRate >= 90) {
            $achievements[] = [
                'type' => 'performance',
                'title' => 'High Performer',
                'description' => "Maintained {$resolutionRate}% resolution rate",
                'icon' => 'trending-up',
                'color' => 'green',
            ];
        }

        // Customer satisfaction
        $satisfaction = $teknisi->getCustomerSatisfactionScore();
        if ($satisfaction && $satisfaction >= 4.5) {
            $achievements[] = [
                'type' => 'satisfaction',
                'title' => 'Customer Favorite',
                'description' => "Achieved {$satisfaction}/5 customer satisfaction",
                'icon' => 'heart',
                'color' => 'red',
            ];
        }

        // Expertise achievements (based on application count from tickets)
        $expertiseCount = $teknisi->assignedTickets()
            ->distinct('aplikasi_id')
            ->count('aplikasi_id');

        if ($expertiseCount >= 3) {
            $achievements[] = [
                'type' => 'expertise',
                'title' => 'Multi-Application Expert',
                'description' => "Handled tickets for {$expertiseCount} applications",
                'icon' => 'academic-cap',
                'color' => 'purple',
            ];
        }

        return $achievements;
    }

    /**
     * Calculate resolution time trend.
     */
    private function calculateResolutionTrend(Teknisi $teknisi, Carbon $startDate, Carbon $endDate): array
    {
        $periods = [];
        $currentStart = $startDate->copy();

        while ($currentStart < $endDate) {
            $periodEnd = $currentStart->copy()->addDays(7); // Weekly periods
            if ($periodEnd > $endDate) {
                $periodEnd = $endDate;
            }

            $avgTime = $teknisi->resolvedTickets()
                ->whereBetween('resolved_at', [$currentStart, $periodEnd])
                ->whereNotNull('resolution_time_minutes')
                ->avg('resolution_time_minutes');

            $periods[] = [
                'period' => $currentStart->format('M j') . ' - ' . $periodEnd->format('M j'),
                'avg_resolution_time' => $avgTime ? round($avgTime / 60, 2) : 0,
            ];

            $currentStart = $periodEnd->copy();
        }

        return $periods;
    }

    /**
     * Get specializations (application expertise) for teknisi.
     */
    private function getSpecializations(Teknisi $teknisi): array
    {
        $applicationIds = $teknisi->assignedTickets()
            ->distinct()
            ->pluck('aplikasi_id')
            ->filter();

        $specializations = [];

        foreach ($applicationIds as $aplikasiId) {
            $application = Aplikasi::find($aplikasiId);

            if ($application) {
                $totalTickets = $teknisi->assignedTickets()
                    ->where('aplikasi_id', $aplikasiId)
                    ->count();

                $resolvedTickets = $teknisi->resolvedTickets()
                    ->where('aplikasi_id', $aplikasiId)
                    ->count();

                // Calculate expertise level based on resolved tickets and success rate
                $resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100) : 0;
                $level = min(100, $resolutionRate);

                $specializations[] = [
                    'name' => $application->name,
                    'level' => $level,
                    'total_tickets' => $totalTickets,
                    'resolved_tickets' => $resolvedTickets,
                ];
            }
        }

        // Sort by level descending
        usort($specializations, function ($a, $b) {
            return $b['level'] - $a['level'];
        });

        return array_slice($specializations, 0, 5); // Return top 5
    }

    /**
     * Get recent feedback for teknisi.
     */
    private function getRecentFeedback(Teknisi $teknisi): array
    {
        $feedbackTickets = $teknisi->resolvedTickets()
            ->whereNotNull('user_rating')
            ->with('user')
            ->latest('resolved_at')
            ->limit(5)
            ->get();

        return $feedbackTickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'user_name' => $ticket->user->nama_lengkap ?? $ticket->user->name ?? 'Unknown User',
                'rating' => $ticket->user_rating,
                'feedback' => $ticket->user_feedback ?? 'No feedback provided',
                'ticket_number' => $ticket->ticket_number,
                'formatted_created_at' => $ticket->resolved_at->diffForHumans(),
            ];
        })->toArray();
    }
}