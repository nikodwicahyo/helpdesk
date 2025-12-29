<?php

namespace Tests\Unit\Services;

use Tests\DatabaseTestCase;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimezoneServiceTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected TimezoneService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TimezoneService();
    }

    /** @test */
    public function it_can_get_all_timezones()
    {
        $timezones = $this->service->getIndonesianTimezones();

        $this->assertIsArray($timezones);
        $this->assertNotEmpty($timezones);
    }

    /** @test */
    public function it_contains_valid_timezones()
    {
        $timezones = $this->service->getIndonesianTimezones();

        $this->assertArrayHasKey('Asia/Jakarta', $timezones);
    }

    /** @test */
    public function it_can_get_timezone_offset()
    {
        $offset = $this->service->getTimezoneOffset('Asia/Jakarta');

        $this->assertIsNumeric($offset);
    }

    /** @test */
    public function it_returns_default_timezone()
    {
        $defaultTimezone = $this->service->getSystemTimezone();

        $this->assertIsString($defaultTimezone);
        $this->assertNotEmpty($defaultTimezone);
    }

    /** @test */
    public function it_validates_timezone()
    {
        $valid = $this->service->isValidTimezone('Asia/Jakarta');
        $invalid = $this->service->isValidTimezone('Invalid/Timezone');

        $this->assertTrue($valid);
        $this->assertFalse($invalid);
    }
}
