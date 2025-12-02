<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:cleanup 
                            {--days=7 : Number of days to keep expired sessions}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired sessions from user_sessions table and Laravel sessions table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting session cleanup...');
        
        $days = (int) $this->option('days');
        $force = $this->option('force');
        
        $cutoffDate = Carbon::now()->subDays($days);
        $sessionTimeout = (int) config('helpdesk.security.session_timeout_minutes', 120);
        $timeoutCutoff = Carbon::now()->subMinutes($sessionTimeout);
        
        $this->line("Cutoff date for expired sessions: {$cutoffDate->toDateTimeString()}");
        $this->line("Session timeout cutoff: {$timeoutCutoff->toDateTimeString()}");
        
        if (!$force && !$this->confirm('Do you want to proceed with cleanup?', true)) {
            $this->info('Cleanup cancelled.');
            return 0;
        }
        
        try {
            // 1. Clean up user_sessions table - remove old expired sessions
            $this->info('Cleaning up user_sessions table...');
            
            $userSessionsDeleted = DB::table('user_sessions')
                ->where('is_active', false)
                ->where('updated_at', '<', $cutoffDate)
                ->delete();
            
            $this->line("  - Deleted {$userSessionsDeleted} old inactive sessions (older than {$days} days)");
            
            // 2. Mark sessions as inactive if they've expired
            $this->info('Marking expired sessions as inactive...');
            
            $markedInactive = DB::table('user_sessions')
                ->where('is_active', true)
                ->where('expires_at', '<', Carbon::now())
                ->update([
                    'is_active' => false,
                    'updated_at' => Carbon::now()
                ]);
            
            $this->line("  - Marked {$markedInactive} sessions as inactive");
            
            // 3. Clean up Laravel sessions table - remove old sessions
            $this->info('Cleaning up Laravel sessions table...');
            
            $laravelSessionsDeleted = DB::table('sessions')
                ->where('last_activity', '<', $timeoutCutoff->timestamp)
                ->delete();
            
            $this->line("  - Deleted {$laravelSessionsDeleted} expired Laravel sessions");
            
            // 4. Get statistics
            $totalActiveSessions = DB::table('user_sessions')
                ->where('is_active', true)
                ->count();
            
            $totalInactiveSessions = DB::table('user_sessions')
                ->where('is_active', false)
                ->count();
            
            $this->newLine();
            $this->info('Cleanup completed successfully!');
            $this->line("Current session statistics:");
            $this->line("  - Active sessions: {$totalActiveSessions}");
            $this->line("  - Inactive sessions: {$totalInactiveSessions}");
            
            // Log the cleanup
            Log::info('Session cleanup completed', [
                'user_sessions_deleted' => $userSessionsDeleted,
                'sessions_marked_inactive' => $markedInactive,
                'laravel_sessions_deleted' => $laravelSessionsDeleted,
                'active_sessions_remaining' => $totalActiveSessions,
                'inactive_sessions_remaining' => $totalInactiveSessions,
                'cutoff_date' => $cutoffDate->toISOString(),
                'timeout_cutoff' => $timeoutCutoff->toISOString()
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            
            Log::error('Session cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}
