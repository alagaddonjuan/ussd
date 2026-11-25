<div style="font-family: Inter, Arial, sans-serif; padding: 24px; color: #0b1323;">
    <h2 style="color: #20b757; margin-bottom: 12px;">You've Been Invited!</h2>
    <p>Hello,</p>
    <p>You have been invited to join <strong>{{ $teamName }}</strong> on {{ config('app.name') }}.</p>
    <p><strong>Login Email:</strong> {{ $email }}</p>
    @if ($temporaryPassword)
        <p><strong>Temporary Password:</strong> {{ $temporaryPassword }}</p>
    @endif
    <p style="margin: 24px 0;">
        <a href="{{ $loginUrl }}" style="background-color: #20b757; color: #ffffff; padding: 12px 24px; border-radius: 999px; text-decoration: none; font-weight: 600;">
            Sign In to Portal
        </a>
    </p>
</div>
