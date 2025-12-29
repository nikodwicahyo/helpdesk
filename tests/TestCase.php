<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use Illuminate\Support\Facades\Auth;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Authenticate as a user (pegawai).
     */
    protected function actingAsUser(?User $user = null): User
    {
        if (!$user) {
            $user = User::factory()->create();
        }

        $this->actingAs($user, 'web');
        
        return $user;
    }

    /**
     * Authenticate as an admin helpdesk.
     */
    protected function actingAsAdminHelpdesk(?AdminHelpdesk $admin = null): AdminHelpdesk
    {
        if (!$admin) {
            $admin = AdminHelpdesk::factory()->create();
        }

        $this->actingAs($admin, 'web');
        
        return $admin;
    }

    /**
     * Authenticate as an admin aplikasi.
     */
    protected function actingAsAdminAplikasi(?AdminAplikasi $admin = null): AdminAplikasi
    {
        if (!$admin) {
            $admin = AdminAplikasi::factory()->create();
        }

        $this->actingAs($admin, 'web');
        
        return $admin;
    }

    /**
     * Authenticate as a teknisi.
     */
    protected function actingAsTeknisi(?Teknisi $teknisi = null): Teknisi
    {
        if (!$teknisi) {
            $teknisi = Teknisi::factory()->create();
        }

        $this->actingAs($teknisi, 'web');
        
        return $teknisi;
    }

    /**
     * Assert that the database has a record matching the given data.
     */
    protected function assertDatabaseHasRecord(string $table, array $data): void
    {
        $this->assertDatabaseHas($table, $data);
    }

    /**
     * Assert that the database does not have a record matching the given data.
     */
    protected function assertDatabaseMissingRecord(string $table, array $data): void
    {
        $this->assertDatabaseMissing($table, $data);
    }

    /**
     * Assert that a notification was sent.
     */
    protected function assertNotificationSent(string $recipientType, int $recipientId, string $type): void
    {
        $this->assertDatabaseHas('notifications', [
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'type' => $type,
        ]);
    }

    /**
     * Assert that a ticket history record was created.
     */
    protected function assertTicketHistoryCreated(int $ticketId, string $action): void
    {
        $this->assertDatabaseHas('ticket_history', [
            'ticket_id' => $ticketId,
            'action' => $action,
        ]);
    }

    /**
     * Create a test ticket with default values.
     */
    protected function createTestTicket(array $attributes = []): \App\Models\Ticket
    {
        return \App\Models\Ticket::factory()->create($attributes);
    }

    /**
     * Get a valid NIP that doesn't exist in any table.
     */
    protected function getUniqueNip(): string
    {
        do {
            $nip = 'TEST' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (
            User::where('nip', $nip)->exists() ||
            AdminHelpdesk::where('nip', $nip)->exists() ||
            AdminAplikasi::where('nip', $nip)->exists() ||
            Teknisi::where('nip', $nip)->exists()
        );

        return $nip;
    }

    /**
     * Get a valid email that doesn't exist in any table.
     */
    protected function getUniqueEmail(): string
    {
        do {
            $email = 'test' . rand(1, 999999) . '@example.com';
        } while (
            User::where('email', $email)->exists() ||
            AdminHelpdesk::where('email', $email)->exists() ||
            AdminAplikasi::where('email', $email)->exists() ||
            Teknisi::where('email', $email)->exists()
        );

        return $email;
    }
}

