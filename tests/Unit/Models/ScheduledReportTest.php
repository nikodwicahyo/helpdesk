<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\ScheduledReport;
use App\Models\AdminHelpdesk;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduledReportTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_scheduled_report()
    {
        $admin = AdminHelpdesk::factory()->create();

        $report = ScheduledReport::create([
            'title' => 'Monthly Performance Report',
            'report_type' => 'performance',
            'schedule_frequency' => 'monthly',
            'schedule_time' => '09:00',
            'created_by' => $admin->nip,
        ]);

        $this->assertDatabaseHas('scheduled_reports', [
            'title' => 'Monthly Performance Report',
            'report_type' => 'performance',
        ]);
    }

    /** @test */
    public function it_can_create_a_daily_scheduled_report()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'daily',
        ]);

        $this->assertEquals('daily', $report->schedule_frequency);
    }

    /** @test */
    public function it_can_create_a_weekly_scheduled_report()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'weekly',
        ]);

        $this->assertEquals('weekly', $report->schedule_frequency);
    }

    /** @test */
    public function it_can_create_a_monthly_scheduled_report()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'monthly',
        ]);

        $this->assertEquals('monthly', $report->schedule_frequency);
    }

    /** @test */
    public function it_can_activate_a_scheduled_report()
    {
        $report = ScheduledReport::factory()->create([
            'is_active' => false,
        ]);

        $report->update(['is_active' => true]);

        $this->assertTrue($report->fresh()->is_active);
    }

    /** @test */
    public function it_can_deactivate_a_scheduled_report()
    {
        $report = ScheduledReport::factory()->create([
            'is_active' => true,
        ]);

        $report->update(['is_active' => false]);

        $this->assertFalse($report->fresh()->is_active);
    }

    /** @test */
    public function it_can_store_parameters_as_array()
    {
        $parameters = [
            'date_range' => 'last_30_days',
            'include_closed' => true,
            'group_by' => 'status',
        ];

        $report = ScheduledReport::factory()->create([
            'parameters' => $parameters,
        ]);

        $this->assertEquals($parameters, $report->parameters);
    }

    /** @test */
    public function it_can_store_filters_as_array()
    {
        $filters = [
            'status' => ['open', 'assigned'],
            'priority' => 'high',
            'department' => 'IT',
        ];

        $report = ScheduledReport::factory()->create([
            'filters' => $filters,
        ]);

        $this->assertEquals($filters, $report->filters);
    }

    /** @test */
    public function it_can_store_recipients_as_array()
    {
        $recipients = [
            'admin@example.com',
            'manager@example.com',
            'team@example.com',
        ];

        $report = ScheduledReport::factory()->create([
            'recipients' => $recipients,
        ]);

        $this->assertEquals($recipients, $report->recipients);
    }

    /** @test */
    public function it_has_creator_relationship()
    {
        $admin = AdminHelpdesk::factory()->create();
        $report = ScheduledReport::factory()->create([
            'created_by' => $admin->nip,
        ]);

        $this->assertEquals($admin->nip, $report->creator->nip);
    }

    /** @test */
    public function it_can_calculate_next_run_daily()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'daily',
            'schedule_time' => '09:00',
        ]);

        $nextRun = $report->calculateNextRun();

        $this->assertInstanceOf(Carbon::class, $nextRun);
        $this->assertTrue($nextRun->isAfter(Carbon::now()));
    }

    /** @test */
    public function it_can_calculate_next_run_weekly()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'weekly',
            'schedule_time' => '09:00',
        ]);

        $nextRun = $report->calculateNextRun();

        $this->assertInstanceOf(Carbon::class, $nextRun);
        $this->assertTrue($nextRun->isAfter(Carbon::now()));
    }

    /** @test */
    public function it_can_calculate_next_run_monthly()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'monthly',
            'schedule_time' => '09:00',
        ]);

        $nextRun = $report->calculateNextRun();

        $this->assertInstanceOf(Carbon::class, $nextRun);
        $this->assertTrue($nextRun->isAfter(Carbon::now()));
    }

    /** @test */
    public function it_can_track_last_run_time()
    {
        $report = ScheduledReport::factory()->create([
            'last_run_at' => null,
        ]);

        $report->update(['last_run_at' => now()]);

        $this->assertNotNull($report->fresh()->last_run_at);
        $this->assertInstanceOf(Carbon::class, $report->fresh()->last_run_at);
    }

    /** @test */
    public function it_can_track_next_run_time()
    {
        $report = ScheduledReport::factory()->create();

        $report->update(['next_run_at' => now()->addDay()]);

        $this->assertNotNull($report->fresh()->next_run_at);
    }

    /** @test */
    public function it_has_formatted_schedule_time()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_time' => '14:30',
        ]);

        $formatted = $report->formatted_schedule_time;

        $this->assertIsString($formatted);
        $this->assertStringContainsString('PM', $formatted);
    }

    /** @test */
    public function it_has_human_frequency_attribute_daily()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'daily',
        ]);

        $this->assertEquals('Daily', $report->human_frequency);
    }

    /** @test */
    public function it_has_human_frequency_attribute_weekly()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'weekly',
        ]);

        $this->assertEquals('Weekly', $report->human_frequency);
    }

    /** @test */
    public function it_has_human_frequency_attribute_monthly()
    {
        $report = ScheduledReport::factory()->create([
            'schedule_frequency' => 'monthly',
        ]);

        $this->assertEquals('Monthly', $report->human_frequency);
    }

    /** @test */
    public function it_can_store_description()
    {
        $description = 'This report shows all performance metrics for the month';

        $report = ScheduledReport::factory()->create([
            'description' => $description,
        ]);

        $this->assertEquals($description, $report->description);
    }

    /** @test */
    public function it_casts_parameters_to_array()
    {
        $report = ScheduledReport::factory()->create();

        $this->assertIsArray($report->parameters);
    }

    /** @test */
    public function it_casts_filters_to_array()
    {
        $report = ScheduledReport::factory()->create();

        $this->assertIsArray($report->filters);
    }

    /** @test */
    public function it_casts_recipients_to_array()
    {
        $report = ScheduledReport::factory()->create();

        $this->assertIsArray($report->recipients);
    }
}
