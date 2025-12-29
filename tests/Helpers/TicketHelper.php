<?php

namespace Tests\Helpers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;

/**
 * Helper class for ticket-related test utilities.
 */
class TicketHelper
{
    /**
     * Create a ticket with default values.
     */
    public static function createTicket(array $attributes = []): Ticket
    {
        $defaults = [
            'user_id' => User::factory()->create()->id,
            'aplikasi_id' => Aplikasi::factory()->create()->id,
            'kategori_masalah_id' => KategoriMasalah::factory()->create()->id,
        ];

        return Ticket::factory()->create(array_merge($defaults, $attributes));
    }

    /**
     * Create an open ticket (unassigned).
     */
    public static function createOpenTicket(array $attributes = []): Ticket
    {
        return self::createTicket(array_merge([
            'status' => 'open',
            'assigned_teknisi_id' => null,
        ], $attributes));
    }

    /**
     * Create an assigned ticket.
     */
    public static function createAssignedTicket(?Teknisi $teknisi = null, array $attributes = []): Ticket
    {
        if (!$teknisi) {
            $teknisi = Teknisi::factory()->create();
        }

        return self::createTicket(array_merge([
            'status' => 'assigned',
            'assigned_teknisi_id' => $teknisi->id,
            'assigned_at' => now(),
        ], $attributes));
    }

    /**
     * Create a ticket in progress.
     */
    public static function createInProgressTicket(?Teknisi $teknisi = null, array $attributes = []): Ticket
    {
        if (!$teknisi) {
            $teknisi = Teknisi::factory()->create();
        }

        return self::createTicket(array_merge([
            'status' => 'in_progress',
            'assigned_teknisi_id' => $teknisi->id,
            'assigned_at' => now(),
        ], $attributes));
    }

    /**
     * Create a resolved ticket.
     */
    public static function createResolvedTicket(?Teknisi $teknisi = null, array $attributes = []): Ticket
    {
        if (!$teknisi) {
            $teknisi = Teknisi::factory()->create();
        }

        return self::createTicket(array_merge([
            'status' => 'resolved',
            'assigned_teknisi_id' => $teknisi->id,
            'assigned_at' => now()->subDays(2),
            'resolved_at' => now(),
        ], $attributes));
    }

    /**
     * Create a closed ticket.
     */
    public static function createClosedTicket(?Teknisi $teknisi = null, array $attributes = []): Ticket
    {
        if (!$teknisi) {
            $teknisi = Teknisi::factory()->create();
        }

        return self::createTicket(array_merge([
            'status' => 'closed',
            'assigned_teknisi_id' => $teknisi->id,
            'assigned_at' => now()->subDays(3),
            'resolved_at' => now()->subDay(),
            'closed_at' => now(),
            'rating' => 5,
        ], $attributes));
    }

    /**
     * Create an urgent ticket.
     */
    public static function createUrgentTicket(array $attributes = []): Ticket
    {
        return self::createTicket(array_merge([
            'prioritas' => 'urgent',
        ], $attributes));
    }

    /**
     * Create a high priority ticket.
     */
    public static function createHighPriorityTicket(array $attributes = []): Ticket
    {
        return self::createTicket(array_merge([
            'prioritas' => 'high',
        ], $attributes));
    }

    /**
     * Create a ticket with attachments.
     */
    public static function createTicketWithAttachments(array $attributes = []): Ticket
    {
        return self::createTicket(array_merge([
            'lampiran' => json_encode([
                'file1.pdf',
                'screenshot.png',
            ]),
        ], $attributes));
    }

    /**
     * Assign a ticket to a teknisi.
     */
    public static function assignTicket(Ticket $ticket, Teknisi $teknisi): Ticket
    {
        $ticket->update([
            'status' => 'assigned',
            'assigned_teknisi_id' => $teknisi->id,
            'assigned_at' => now(),
        ]);

        return $ticket->fresh();
    }
}
