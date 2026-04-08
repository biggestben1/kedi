<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 20px 0; }
        .container { max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .brand-bar { background: linear-gradient(90deg, #f97316, #ef4444); padding: 16px 24px; display: flex; align-items: center; gap: 12px; color: #ffffff; }
        .brand-bar img { max-height: 40px; max-width: 140px; display: block; }
        .brand-bar-title { font-size: 18px; font-weight: 600; }
        .inner { padding: 24px; }
        .content { margin-bottom: 20px; }
        .section-title { margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #0f172a; }
        .info-box { background-color: #f9fafb; border-radius: 6px; padding: 12px 16px; border: 1px solid #e5e7eb; }
        .label { font-weight: 600; color: #111827; }
        .footer { color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 24px; }
        a { color: #f97316; text-decoration: none; }
        a:hover { text-decoration: underline; }
        @media (max-width: 640px) {
            .inner { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="brand-bar">
                <img src="{{ config('app.url') }}/images/logo.png" alt="{{ config('app.name') }} Logo"
                     onerror="this.style.display='none'">
                <div class="brand-bar-title">{{ config('app.name') }}</div>
            </div>

            <div class="inner">
                <div class="content">
                    <p style="margin:0 0 4px 0;">Dear {{ $user->name }},</p>

                    <p style="margin:0 0 12px 0;">
                        Your <strong>Dispatch account</strong> has been created successfully.
                    </p>

                    <p style="margin:0 0 12px 0;">
                        You can now manage deliveries, track orders, and update dispatch status.
                    </p>

                    <h3 class="section-title">Login Details</h3>
                    <div class="info-box" style="margin-bottom: 16px;">
                        <p style="margin:0 0 4px 0;">
                            <span class="label">Email:</span> {{ $user->email }}
                        </p>
                        <p style="margin:0 0 4px 0;">
                            <span class="label">Password:</span> {{ $tempPassword }}
                        </p>
                        <p style="margin:0;">
                            <span class="label">Login Link:</span>
                            <a href="{{ config('app.url') }}/login">{{ config('app.url') }}/login</a>
                        </p>
                    </div>

                    <p style="margin:0 0 12px 0;">
                        Please log in and update your password immediately.
                    </p>

                    <p style="margin:0;">
                        Thank you,<br>
                        Admin
                    </p>
                </div>

                <div class="footer">
                    <p style="margin:0;">
                        {{ config('app.name') }} – <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 20px 0; }
        .container { max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .brand-bar { background: linear-gradient(90deg, #f59e0b, #ef4444); padding: 16px 24px; display: flex; align-items: center; gap: 12px; color: #ffffff; }
        .brand-bar img { max-height: 40px; max-width: 140px; display: block; }
        .brand-bar-title { font-size: 18px; font-weight: 600; }
        .inner { padding: 24px; }
        .content { margin-bottom: 20px; }
        .section-title { margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #0f172a; }
        .info-box { background-color: #f9fafb; border-radius: 6px; padding: 12px 16px; border: 1px solid #e5e7eb; }
        .label { font-weight: 600; color: #111827; }
        .footer { color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 24px; }
        a { color: #f59e0b; text-decoration: none; }
        a:hover { text-decoration: underline; }
        @media (max-width: 640px) {
            .inner { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="brand-bar">
                <img src="{{ config('app.url') }}/images/logo.png" alt="{{ config('app.name') }} Logo"
                     onerror="this.style.display='none'">
                <div class="brand-bar-title">{{ config('app.name') }}</div>
            </div>

            <div class="inner">
                <div class="content">
                    <p style="margin:0 0 4px 0;">Dear {{ $user->name }},</p>

                    <p style="margin:0 0 12px 0;">
                        Your <strong>Dispatch account</strong> has been created successfully.
                    </p>

                    <p style="margin:0 0 12px 0;">
                        You can now manage deliveries, track orders, and update dispatch status.
                    </p>

                    <h3 class="section-title">Login Details</h3>
                    <div class="info-box" style="margin-bottom: 16px;">
                        <p style="margin:0 0 4px 0;">
                            <span class="label">Email:</span> {{ $user->email }}
                        </p>
                        <p style="margin:0 0 4px 0;">
                            <span class="label">Password:</span> {{ $tempPassword }}
                        </p>
                        <p style="margin:0;">
                            <span class="label">Login Link:</span>
                            <a href="{{ config('app.url') }}/login">{{ config('app.url') }}/login</a>
                        </p>
                    </div>

                    <p style="margin:0 0 12px 0;">
                        Please log in and update your password immediately.
                    </p>

                    <p style="margin:0 0 0 0;">
                        Thank you,<br>
                        Admin
                    </p>
                </div>

                <div class="footer">
                    <p style="margin:0;">
                        {{ config('app.name') }} – <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

