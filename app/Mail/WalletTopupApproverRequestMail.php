<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WalletTopupApproverRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $approver,
        public User $requester,
        public float $amount,
        public string $date,
        public int $transactionId,
        public string $sourceUnit,
        public string $paymentMethod,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Wallet funding request pending approval'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wallet-topup-approver-request',
            with: [
                'approver' => $this->approver,
                'requester' => $this->requester,
                'amount' => $this->amount,
                'date' => $this->date,
                'transactionId' => $this->transactionId,
                'sourceUnit' => $this->sourceUnit,
                'paymentMethod' => $this->paymentMethod,
                'approvalLink' => url(route('admin.wallet_topups')),
            ],
        );
    }
}
