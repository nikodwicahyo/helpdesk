<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;

class RecalculateResolutionTimes extends Command
{
    protected $signature = 'tickets:recalculate-resolution-times';
    protected $description = 'Recalculate resolution_time_minutes for all resolved tickets that have resolved_at but no resolution_time_minutes';

    public function handle()
    {
        $this->info('Finding resolved tickets without resolution_time_minutes...');

        $tickets = Ticket::whereNotNull('resolved_at')
            ->whereNull('resolution_time_minutes')
            ->get();

        $count = $tickets->count();
        $this->info("Found {$count} tickets to update.");

        if ($count === 0) {
            $this->info('No tickets need updating.');
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;
        foreach ($tickets as $ticket) {
            $resolutionTime = $ticket->calculateResolutionTime();
            if ($resolutionTime !== null) {
                $ticket->resolution_time_minutes = $resolutionTime;
                $ticket->save();
                $updated++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated {$updated} tickets.");

        return 0;
    }
}
