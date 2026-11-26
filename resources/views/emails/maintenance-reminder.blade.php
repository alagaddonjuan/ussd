<!DOCTYPE html>
<html lang="en">
<body style="font-family: Inter, Arial, sans-serif; background-color: #F8FAFC; padding: 28px; color: #1F2937;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #D1D5DB; border-radius: 16px; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #0B3A75 0%, #0878F8 100%); padding: 22px 28px; color: #ffffff;">
            <h2 style="margin: 0; font-size: 20px;">Monthly Channel Maintenance Notice</h2>
            <p style="margin: 6px 0 0; font-size: 13px; color: #DBEAFE;">{{ config('app.name', 'Q4I Comms') }} Billing</p>
        </div>
        <div style="padding: 28px;">
            <p style="margin-top: 0;">Hello {{ $client->name }},</p>
            <p>
                This is your advance notice that your monthly USSD channel maintenance fee of
                <strong>₦{{ number_format($feeAmount, 2) }}</strong> is scheduled for billing on the
                <strong>1st of next month</strong>.
            </p>
            <p style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px; font-size: 13px;">
                Current Wallet Balance: <strong>₦{{ number_format((float) $client->token_balance, 2) }}</strong><br />
                Assigned Shortcode: <strong>{{ $client->ussd_code ?: 'Pending' }}</strong>
            </p>
            <p>Please ensure your wallet is funded before the billing cycle so your USSD services continue without interruption.</p>
            <p style="margin: 26px 0;">
                <a href="{{ route('billing.index') }}" style="display: inline-block; background-color: #0878F8; color: #ffffff; padding: 12px 26px; border-radius: 999px; text-decoration: none; font-weight: 700;">
                    Open Billing Console
                </a>
            </p>
            <p style="font-size: 12px; color: #6B7280; margin-bottom: 0;">Q4 International Ltd &bull; support@q4iltd.com</p>
        </div>
    </div>
</body>
</html>
