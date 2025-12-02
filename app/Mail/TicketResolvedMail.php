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

class TicketResolvedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $ticket;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket, User $user)
    {
        $this->ticket = $ticket;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Anda Telah Diselesaikan - ' . $this->ticket->ticket_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-resolved',
            with: [
                'ticket' => $this->ticket,
                'user' => $this->user,
                'feedback_url' => route('user.feedback', $this->ticket->id),
                'ticket_history_url' => route('user.ticket-history.show', $this->ticket->id),
                'resolution_summary' => $this->getResolutionSummary($this->ticket->resolution),
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
     * Get formatted resolution summary.
     */
    private function getResolutionSummary($resolution)
    {
        if (!$resolution) {
            return 'Ticket telah diselesaikan oleh teknisi yang bertugas.';
        }

        // Truncate long resolutions for email display
        if (strlen($resolution) > 200) {
            return substr($resolution, 0, 200) . '...';
        }

        return $resolution;
    }
}