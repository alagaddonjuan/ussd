<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #1F2937; background-color: #F8FAFC; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #D1D5DB; border-radius: 14px; padding: 28px;">
        <h2 style="margin-top: 0; color: #0B3A75;">Low Wallet Balance Notice</h2>
        <p>Hello {{ $client->name }},</p>
        <p>Your {{ config('app.name', 'Q4I Comms') }} wallet balance has dropped to <strong>₦{{ number_format((float) $client->token_balance, 2) }}</strong>, which is at or below your alert threshold of <strong>₦{{ number_format($threshold, 2) }}</strong>.</p>
        <p>Please fund your wallet to avoid interruption to your active USSD shortcodes, Bulk SMS, or Airtime services.</p>
        <p style="margin-top: 24px;">
            <a href="{{ route('billing.index') }}" style="display: inline-block; background: #0878F8; color: #ffffff; text-decoration: none; padding: 12px 22px; border-radius: 8px; font-weight: bold;">
                Fund Wallet Now
            </a>
        </p>
        <p style="font-size: 12px; color: #6B7280; margin-top: 24px;">
            Q4 International Ltd &bull; support@q4iltd.com
        </p>
    </div>
</body>
</html>
