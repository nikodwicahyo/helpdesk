<?php

namespace Tests\Unit\Services;

use Tests\DatabaseTestCase;
use App\Services\SearchService;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchServiceTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = new SearchService();
    }

    /** @test */
    public function it_can_search_tickets_by_title_using_db_query()
    {
        $user = User::factory()->create();
        $ticket1 = Ticket::factory()->create([
            'title' => 'Unique Issue Alpha',
            'user_nip' => $user->nip
        ]);
        $ticket2 = Ticket::factory()->create([
            'title' => 'Common Problem',
            'user_nip' => $user->nip
        ]);

        // Use Scout = false to force DB query
        $result = $this->searchService->searchTickets(['query' => 'Alpha'], 15, false);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals($ticket1->id, $result['tickets'][0]->id);
    }

    /** @test */
    public function it_can_filter_tickets_by_status()
    {
        $openTicket = Ticket::factory()->create(['status' => 'open']);
        $closedTicket = Ticket::factory()->create(['status' => 'closed']);

        $result = $this->searchService->searchTickets(['status' => 'open'], 15, false);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals($openTicket->id, $result['tickets'][0]->id);
    }

    /** @test */
    public function it_can_filter_tickets_by_priority()
    {
        $highTicket = Ticket::factory()->create(['priority' => 'high']);
        $lowTicket = Ticket::factory()->create(['priority' => 'low']);

        $result = $this->searchService->searchTickets(['priority' => 'high'], 15, false);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals($highTicket->id, $result['tickets'][0]->id);
    }
}
