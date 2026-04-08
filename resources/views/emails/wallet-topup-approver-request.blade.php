<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Top-up Approval Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 20px 0; }
        .container { max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .inner { padding: 24px; }
        .content p { margin: 0 0 12px 0; }
        .header { font-size: 16px; font-weight: 700; margin: 0 0 12px 0; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 16px; margin: 12px 0; }
        .muted { color: #6b7280; font-size: 12px; }
        a { color: #7c3aed; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .footer { color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; padding: 16px 24px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="inner content">
                <p>Dear {{ $approver->name }},</p>

                <p>
                    A new wallet funding request has been submitted and requires your approval.
                </p>

                <div class="box">
                    <p class="header">👤 User Details:</p>
                    <p style="margin:0 0 8px 0;"><strong>Name:</strong> {{ $requester->name }}</p>
                    <p style="margin:0;"><strong>Email:</strong> {{ $requester->email }}</p>
                </div>

                <div class="box">
                    <p class="header">💰 Transaction Details:</p>
                    <p style="margin:0 0 8px 0;"><strong>Amount:</strong> ₦{{ number_format($amount, 2) }}</p>
                    <p style="margin:0 0 8px 0;"><strong>Payment Method:</strong> {{ $paymentMethod }}</p>
                    <p style="margin:0 0 8px 0;"><strong>Transaction ID:</strong> {{ $transactionId }}</p>
                    <p style="margin:0;"><strong>Date:</strong> {{ $date }}</p>
                </div>

                <div class="box">
                    <p class="header">🏢 Source Unit: {{ $sourceUnit }}</p>
                </div>

                <p class="muted" style="margin-top:0;">
                    Please review the payment and take the appropriate action.
                </p>

                <p>
                    👉 Approve or Reject here:
                    <a href="{{ $approvalLink }}">Wallet Top-ups</a>
                </p>

                <p class="muted">
                    Kindly ensure proper verification before approval.
                </p>

                <p>Best regards,<br>System Notification</p>
            </div>

            <div class="footer">
                {{ config('app.name') }} – <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
            </div>
        </div>
    </div>
</body>
</html>

