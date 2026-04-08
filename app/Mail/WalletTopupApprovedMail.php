<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WalletTopupApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public float $amount,
        public string $date,
        public int $transactionId,
        public float $balance,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your wallet has been funded successfully',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wallet-topup-approved',
            with: [
                'user' => $this->user,
                'amount' => $this->amount,
                'date' => $this->date,
                'transactionId' => $this->transactionId,
                'balance' => $this->balance,
            ],
        );
    }
}
