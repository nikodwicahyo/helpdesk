<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Services\SystemSettingsService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CheckSlaBreaches extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:check-sla-breaches
                            {--notify : Send notifications for SLA breaches}
                            {--dry-run : Show breaches without taking action}';

    /**
     * The console command description.
     */
    protected $description = 'Check for SLA breaches and escalate unassigned urgent/high priority tickets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $notify = $this->option('notify');
        $dryRun = $this->option('dry-run');

        $this->info('Checking for SLA breaches and escalation requirements...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actions will be taken');
        }

        try {
            $escalationResults = $this->checkEscalations($notify, $dryRun);
            $slaBreachResults = $this->checkSlaBreaches($notify, $dryRun);

            $this->newLine();
            $this->info('SLA Check Summary:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Urgent tickets needing escalation', $escalationResults['urgent']],
                    ['High priority tickets needing escalation', $escalationResults['high']],
                    ['Response SLA breaches', $slaBreachResults['response']],
                    ['Resolution SLA breaches', $slaBreachResults['resolution']],
                ]
            );

            Log::info('SLA check completed', [
                'escalations' => $escalationResults,
                'sla_breaches' => $slaBreachResults,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("SLA check failed: " . $e->getMessage());

            Log::error('SLA check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Check for tickets that need escalation.
     */
    protected function checkEscalations(bool $notify, bool $dryRun): array
    {
        $urgentHours = (float) SystemSetting::get('escalation_urgent_hours', 2);
        $highHours = (float) SystemSetting::get('escalation_high_hours', 4);

        $results = ['urgent' => 0, 'high' => 0];

        // Check urgent tickets
        $urgentCutoff = now()->subHours($urgentHours);
        $urgentTickets = Ticket::where('status', Ticket::STATUS_OPEN)
            ->where('priority', Ticket::PRIORITY_URGENT)
            ->whereNull('assigned_teknisi_nip')
            ->where('created_at', '<=', $urgentCutoff)
            ->get();

        if ($urgentTickets->isNotEmpty()) {
            $results['urgent'] = $urgentTickets->count();
            $this->warn("Found {$results['urgent']} unassigned urgent ticket(s) older than {$urgentHours} hours:");

            foreach ($urgentTickets as $ticket) {
                $this->line("  - {$ticket->ticket_number}: {$ticket->title}");

                if (!$dryRun && $notify) {
                    $this->sendEscalationNotification($ticket, 'urgent');
                }
            }
        }

        // Check high priority tickets
        $highCutoff = now()->subHours($highHours);
        $highTickets = Ticket::where('status', Ticket::STATUS_OPEN)
            ->where('priority', Ticket::PRIORITY_HIGH)
            ->whereNull('assigned_teknisi_nip')
            ->where('created_at', '<=', $highCutoff)
            ->get();

        if ($highTickets->isNotEmpty()) {
            $results['high'] = $highTickets->count();
            $this->warn("Found {$results['high']} unassigned high priority ticket(s) older than {$highHours} hours:");

            foreach ($highTickets as $ticket) {
                $this->line("  - {$ticket->ticket_number}: {$ticket->title}");

                if (!$dryRun && $notify) {
                    $this->sendEscalationNotification($ticket, 'high');
                }
            }
        }

        return $results;
    }

    /**
     * Check for SLA breaches.
     */
    protected function checkSlaBreaches(bool $notify, bool $dryRun): array
    {
        $results = ['response' => 0, 'resolution' => 0];
        $workingHours = SystemSettingsService::getWorkingHours();

        // Get open/assigned tickets and check their SLA status
        $activeTickets = Ticket::whereIn('status', [
            Ticket::STATUS_OPEN,
            Ticket::STATUS_ASSIGNED,
            Ticket::STATUS_IN_PROGRESS,
        ])->get();

        foreach ($activeTickets as $ticket) {
            $sla = SystemSettingsService::getSlaForPriority($ticket->priority);
            $createdAt = $ticket->created_at;
            $now = now();

            // Calculate business hours elapsed
            $hoursElapsed = $this->calculateBusinessHours($createdAt, $now, $workingHours);

            // Check response SLA (first response)
            if (!$ticket->first_response_at) {
                if ($hoursElapsed > $sla['response_hours']) {
                    $results['response']++;
                    $this->line("Response SLA breach: {$ticket->ticket_number} ({$ticket->priority}) - {$hoursElapsed}h elapsed, SLA: {$sla['response_hours']}h");
                }
            }

            // Check resolution SLA
            if ($hoursElapsed > $sla['resolution_hours']) {
                $results['resolution']++;
                $this->line("Resolution SLA breach: {$ticket->ticket_number} ({$ticket->priority}) - {$hoursElapsed}h elapsed, SLA: {$sla['resolution_hours']}h");
            }
        }

        return $results;
    }

    /**
     * Calculate business hours between two dates.
     */
    protected function calculateBusinessHours($start, $end, array $workingHours): float
    {
        $start = \Carbon\Carbon::parse($start);
        $end = \Carbon\Carbon::parse($end);
        $workDays = $workingHours['days'];
        
        list($startHour, $startMinute) = explode(':', $workingHours['start']);
        list($endHour, $endMinute) = explode(':', $workingHours['end']);
        
        $workStartMinutes = (int)$startHour * 60 + (int)$startMinute;
        $workEndMinutes = (int)$endHour * 60 + (int)$endMinute;
        $workDayMinutes = $workEndMinutes - $workStartMinutes;

        $totalMinutes = 0;
        $current = $start->copy();

        while ($current < $end) {
            $dayOfWeek = $current->dayOfWeek;
            
            // Check if it's a working day (0 = Sunday, 1 = Monday, etc.)
            if (in_array($dayOfWeek, $workDays)) {
                $dayStart = $current->copy()->setTime((int)$startHour, (int)$startMinute, 0);
                $dayEnd = $current->copy()->setTime((int)$endHour, (int)$endMinute, 0);

                $effectiveStart = max($current, $dayStart);
                $effectiveEnd = min($end, $dayEnd);

                if ($effectiveEnd > $effectiveStart) {
                    $totalMinutes += $effectiveEnd->diffInMinutes($effectiveStart);
                }
            }

            $current->addDay()->startOfDay();
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Send escalation notification.
     */
    protected function sendEscalationNotification(Ticket $ticket, string $priority): void
    {
        try {
            // Create notification for admin helpdesk about escalation
            Notification::create([
                'recipient_nip' => null, // Will be handled by admin helpdesk
                'recipient_type' => 'admin_helpdesk',
                'type' => 'escalation',
                'title' => "Escalation: Unassigned {$priority} priority ticket",
                'message' => "Ticket {$ticket->ticket_number} requires immediate attention. It has been unassigned for too long.",
                'data' => json_encode([
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'priority' => $priority,
                    'created_at' => $ticket->created_at->toISOString(),
                ]),
                'is_read' => false,
                'related_type' => 'ticket',
                'related_id' => $ticket->id,
            ]);

            Log::info('Escalation notification sent', [
                'ticket_id' => $ticket->id,
                'priority' => $priority,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send escalation notification', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
