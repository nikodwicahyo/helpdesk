<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use App\Models\AdminHelpdesk;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScheduledReportController extends Controller
{
    /**
     * Store a newly created scheduled report in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'report_type' => 'required|string|in:tickets,performance,user_activity,sla,application,summary',
            'schedule_frequency' => 'required|string|in:daily,weekly,monthly',
            'schedule_time' => 'required|date_format:H:i',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email',
            'parameters' => 'nullable|array',
            'filters' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $scheduledReport = ScheduledReport::create([
                'title' => $request->title,
                'report_type' => $request->report_type,
                'parameters' => $request->parameters ?? [],
                'filters' => $request->filters ?? [],
                'schedule_frequency' => $request->schedule_frequency,
                'schedule_time' => $request->schedule_time,
                'recipients' => $request->recipients,
                'description' => $request->description,
                'created_by' => Auth::guard('admin_helpdesk')->id(),
                'next_run_at' => $this->calculateInitialNextRun($request->schedule_frequency, $request->schedule_time),
            ]);

            return response()->json([
                'message' => 'Report scheduled successfully!',
                'scheduled_report' => $scheduledReport
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to schedule report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified scheduled report in storage.
     */
    public function update(Request $request, ScheduledReport $scheduledReport): JsonResponse
    {
        // Check if user can edit this scheduled report
        if ($scheduledReport->created_by !== Auth::guard('admin_helpdesk')->id()) {
            return response()->json([
                'message' => 'You do not have permission to edit this scheduled report.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'report_type' => 'required|string|in:tickets,performance,user_activity,sla,application,summary',
            'schedule_frequency' => 'required|string|in:daily,weekly,monthly',
            'schedule_time' => 'required|date_format:H:i',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email',
            'parameters' => 'nullable|array',
            'filters' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $scheduledReport->update([
                'title' => $request->title,
                'report_type' => $request->report_type,
                'parameters' => $request->parameters ?? [],
                'filters' => $request->filters ?? [],
                'schedule_frequency' => $request->schedule_frequency,
                'schedule_time' => $request->schedule_time,
                'recipients' => $request->recipients,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', $scheduledReport->is_active),
            ]);

            // Recalculate next run if schedule changed
            if ($scheduledReport->wasChanged(['schedule_frequency', 'schedule_time'])) {
                $scheduledReport->update([
                    'next_run_at' => $scheduledReport->calculateNextRun()
                ]);
            }

            return response()->json([
                'message' => 'Scheduled report updated successfully!',
                'scheduled_report' => $scheduledReport->refresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update scheduled report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified scheduled report from storage.
     */
    public function destroy(ScheduledReport $scheduledReport): JsonResponse
    {
        // Check if user can delete this scheduled report
        if ($scheduledReport->created_by !== Auth::guard('admin_helpdesk')->id()) {
            return response()->json([
                'message' => 'You do not have permission to delete this scheduled report.'
            ], 403);
        }

        try {
            $scheduledReport->delete();

            return response()->json([
                'message' => 'Scheduled report deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete scheduled report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of scheduled reports for current user
     */
    public function index(): JsonResponse
    {
        $scheduledReports = ScheduledReport::where('created_by', Auth::guard('admin_helpdesk')->id())
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($scheduledReports);
    }

    /**
     * Calculate initial next run time
     */
    private function calculateInitialNextRun(string $frequency, string $time): \Carbon\Carbon
    {
        $now = now();
        $scheduleTime = \Carbon\Carbon::parse($time);
        $nextRun = $now->copy()->setTimeFrom($scheduleTime);

        // If the time for today has passed, schedule for next period
        if ($nextRun->isPast()) {
            switch ($frequency) {
                case 'daily':
                    $nextRun->addDay();
                    break;
                case 'weekly':
                    $nextRun->addWeek();
                    break;
                case 'monthly':
                    $nextRun->addMonth();
                    break;
            }
        }

        return $nextRun;
    }
}
