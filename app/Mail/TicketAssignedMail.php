<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Teknisi;

class TicketAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $ticket;
    public $user;
    public $teknisi;

    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket, User $user, Teknisi $teknisi)
    {
        $this->ticket = $ticket;
        $this->user = $user;
        $this->teknisi = $teknisi;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Baru Ditetapkan - ' . $this->ticket->ticket_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-assigned',
            with: [
                'ticket' => $this->ticket,
                'user' => $this->user,
                'teknisi' => $this->teknisi,
                'url' => route('teknisi.ticket-handling.show', $this->ticket->id),
                'priority' => $this->getPriorityLabel($this->ticket->priority),
                'sla_deadline' => $this->calculateSLADeadline($this->ticket->created_at, $this->ticket->priority),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get priority label in Indonesian.
     */
    private function getPriorityLabel($priority)
    {
        return match($priority) {
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'critical' => 'Kritis',
            default => 'Tidak Diketahui'
        };
    }

    /**
     * Calculate SLA deadline based on priority.
     */
    private function calculateSLADeadline($created_at, $priority)
    {
        $hours = match($priority) {
            'low' => 72,      // 3 days
            'medium' => 48,   // 2 days
            'high' => 24,     // 1 day
            'critical' => 4,  // 4 hours
            default => 48
        };

        return $created_at->copy()->addHours($hours);
    }
}