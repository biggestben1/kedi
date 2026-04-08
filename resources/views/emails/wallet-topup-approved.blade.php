<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Wallet Top-up Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 20px 0; }
        .container { max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .inner { padding: 24px; }
        .content p { margin: 0 0 12px 0; }
        .footer { color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; padding: 16px 24px; }
        .muted { color: #6b7280; }
        .small { font-size: 12px; }
        a { color: #7c3aed; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="inner content">
                <p>Dear {{ $user->name }},</p>

                <p>
                    We are pleased to inform you that your wallet has been successfully funded.
                </p>

                <p>
                    <span class="badge">Amount Added: ₦{{ number_format($amount, 2) }}</span><br>
                    <span class="badge">Date: {{ $date }}</span><br>
                    <span class="badge">Transaction ID: {{ $transactionId }}</span><br>
                </p>

                <p>Your updated wallet balance is now: ₦{{ number_format($balance, 2) }}</p>

                <p class="muted">
                    You can now proceed with transactions on the platform without interruption.
                </p>

                <p class="muted">
                    If you did not initiate this transaction, please contact support immediately.
                </p>

                <p>Thank you for choosing us.</p>

                <p>Best regards,<br>Management</p>
            </div>

            <div class="footer">
                {{ config('app.name') }} - <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
            </div>
        </div>
    </div>
</body>
</html>

