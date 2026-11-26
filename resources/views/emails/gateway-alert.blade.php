<!DOCTYPE html>
<html lang="en">
<body style="font-family: Inter, Arial, sans-serif; background-color: #F8FAFC; padding: 28px; color: #1F2937;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #FCA5A5; border-radius: 16px; overflow: hidden;">
        <div style="background: #991B1B; padding: 20px 28px; color: #ffffff;">
            <h2 style="margin: 0; font-size: 19px;">Gateway Anomaly Alert</h2>
            <p style="margin: 4px 0 0; font-size: 12px; color: #FECACA;">{{ config('app.name', 'Q4I Comms') }} Health Monitor</p>
        </div>
        <div style="padding: 26px;">
            <p style="margin-top: 0;">
                <strong>{{ $errorCount }}</strong> webhook or gateway errors were recorded within the past
                <strong>{{ $windowMinutes }} minutes</strong>.
            </p>
            @if ($latestOutcome)
                <p style="font-size: 13px; color: #4B5563;">Latest Outcome: <code>{{ $latestOutcome }}</code></p>
            @endif
            <p style="margin: 22px 0;">
                <a href="{{ route('admin.logs', ['tab' => 'webhooks']) }}" style="display: inline-block; background-color: #0878F8; color: #ffffff; padding: 11px 24px; border-radius: 999px; text-decoration: none; font-weight: 700;">
                    Inspect Webhook Logs
                </a>
            </p>
            <p style="font-size: 12px; color: #6B7280; margin-bottom: 0;">Q4 International Ltd Automated Monitor</p>
        </div>
    </div>
</body>
</html>
