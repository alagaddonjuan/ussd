<div style="font-family: Inter, Arial, sans-serif; padding: 24px; color: #0b1323;">
    <h2 style="color: #20b757; margin-bottom: 12px;">Reset Your Password</h2>
    <p>Hello {{ $clientName }},</p>
    <p>We received a request to reset your {{ config('app.name') }} password. Click the button below within 60 minutes to choose a new password:</p>
    <p style="margin: 24px 0;">
        <a href="{{ $resetUrl }}" style="background-color: #20b757; color: #ffffff; padding: 12px 24px; border-radius: 999px; text-decoration: none; font-weight: 600;">
            Reset Password
        </a>
    </p>
    <p style="font-size: 13px; color: #6a7283;">If you did not request a password reset, you can safely ignore this email.</p>
</div>
