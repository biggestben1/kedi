<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $tempPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Branch Account Has Been Created',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.branch-welcome',
            with: [
                'user' => $this->user,
                'tempPassword' => $this->tempPassword,
            ],
        );
    }
}
