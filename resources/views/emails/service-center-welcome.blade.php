<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .content { margin-bottom: 20px; }
        .footer { color: #666; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px; }
        .button { display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to KEDI 🎉</h1>
            <p>Congratulations! Your Service Center account has been successfully created on <strong>KEDI</strong>.</p>
        </div>

        <div class="content">
            <h2>Your Login Details</h2>
            <p>
                <span class="label">Username:</span> {{ $user->email }}<br>
                <span class="label">Temporary Password:</span> {{ $tempPassword }}
            </p>

            <p style="margin-top: 20px;">
                <a href="{{ config('app.url') }}/login" class="button">Login to KEDI</a>
            </p>

            <hr>

            <h3>⚠️ Important</h3>
            <p>For security reasons, please log in immediately and change your password after your first login.</p>

            <h3>What Happens Next?</h3>
            <ul>
                <li>Our team will review and verify your service center details.</li>
                <li>Once verification is completed, your center will become fully active and visible on the platform.</li>
                <li>If any additional information is required, we will contact you via this email address.</li>
            </ul>

            <h3>Need Assistance?</h3>
            <p>
                If you experience any issues logging in, please contact our support team:<br>
                📧 support@kedi.org<br>
                🌐 {{ config('app.url') }}
            </p>

            <p>We are excited to have you onboard and look forward to working with you.</p>
        </div>

        <div class="footer">
            <p>
                Best regards,<br>
                <strong>The KEDI Team</strong><br>
                {{ config('app.url') }}
            </p>
        </div>
    </div>
</body>
</html>
