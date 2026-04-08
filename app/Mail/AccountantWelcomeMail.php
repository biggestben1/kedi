<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $tempPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Accountant Access Has Been Granted',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.accountant-welcome',
            with: [
                'user' => $this->user,
                'tempPassword' => $this->tempPassword,
            ],
        );
    }
}
