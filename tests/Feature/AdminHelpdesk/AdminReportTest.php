<?php

namespace Tests\Feature\AdminHelpdesk;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\AdminHelpdesk;
use App\Models\Ticket;
use Carbon\Carbon;

class AdminReportTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user with report viewer permission or full admin
        $this->admin = AdminHelpdesk::factory()->create(['permissions' => ['report_viewer']]);
    }

    /** @test */
    public function admin_can_view_reports_dashboard()
    {
        $this->actingAs($this->admin, 'admin_helpdesk');

        $response = $this->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AdminHelpdesk/Reports')
        );
    }

    /** @test */
    public function admin_can_generate_ticket_report()
    {
        $this->actingAs($this->admin, 'admin_helpdesk');

        Ticket::factory()->count(10)->create([
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        $params = [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'type' => 'tickets' // Not used by custom endpoint directly but kept for context if needed
        ];

        $response = $this->postJson('/admin/reports/custom', $params);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'filters',
                     'period',
                     'summary',
                     'tickets'
                 ]);
    }

    /** @test */
    public function admin_can_export_report_pdf()
    {
        $this->actingAs($this->admin);

        $params = [
            'type' => 'performance', 
            'format' => 'pdf',
            'date_from' => Carbon::now()->subMonth()->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d'),
        ];

        // This endpoint logic is correct based on ReportController::export validation
        $response = $this->get('/admin/reports/export?' . http_build_query($params));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
