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
use App\Models\AdminHelpdesk;

class TicketCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $ticket;
    public $user;
    public $adminHelpdesk;

    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket, User $user, AdminHelpdesk $adminHelpdesk)
    {
        $this->ticket = $ticket;
        $this->user = $user;
        $this->adminHelpdesk = $adminHelpdesk;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Baru Dibuat - ' . $this->ticket->ticket_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-created',
            with: [
                'ticket' => $this->ticket,
                'user' => $this->user,
                'adminHelpdesk' => $this->adminHelpdesk,
                'url' => route('admin.tickets.show', $this->ticket->id),
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
}