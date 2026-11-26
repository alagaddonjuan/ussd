<!DOCTYPE html>
<html lang="en">
<body style="font-family: Inter, Arial, sans-serif; background-color: #F8FAFC; padding: 28px; color: #1F2937;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #D1D5DB; border-radius: 16px; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #0B3A75 0%, #0878F8 100%); padding: 22px 28px; color: #ffffff;">
            <h2 style="margin: 0; font-size: 20px;">Password Reset Request</h2>
            <p style="margin: 6px 0 0; font-size: 13px; color: #DBEAFE;">{{ config('app.name', 'Q4I Comms') }} Security</p>
        </div>
        <div style="padding: 28px;">
            <p style="margin-top: 0;">Hello {{ $clientName }},</p>
            <p>We received a request to reset your {{ config('app.name', 'Q4I Comms') }} account password. Click the button below within <strong>60 minutes</strong> to choose a new password:</p>
            <p style="margin: 26px 0;">
                <a href="{{ $resetUrl }}" style="display: inline-block; background-color: #0878F8; color: #ffffff; padding: 12px 26px; border-radius: 999px; text-decoration: none; font-weight: 700;">
                    Reset Password
                </a>
            </p>
            <p style="font-size: 12px; color: #6B7280; word-break: break-all;">
                If the button does not work, copy and paste this link:<br />
                <a href="{{ $resetUrl }}" style="color: #0878F8;">{{ $resetUrl }}</a>
            </p>
            <p style="font-size: 12px; color: #6B7280; margin-bottom: 0;">
                If you did not request a password reset, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
