<?php

namespace App\Events;

use App\Models\TicketComment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Real-time broadcasting has been removed

class CommentAdded
{
    use Dispatchable, SerializesModels;

    public $comment;
    public $ticket;
    public $commenter;

    /**
     * Create a new event instance.
     */
    public function __construct(TicketComment $comment, Ticket $ticket, User $commenter)
    {
        $this->comment = $comment;
        $this->ticket = $ticket;
        $this->commenter = $commenter;
    }
}