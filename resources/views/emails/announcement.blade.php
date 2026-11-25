<div style="font-family: Inter, Arial, sans-serif; padding: 24px; color: #0b1323;">
    <h2 style="color: #20b757; margin-bottom: 12px;">{{ $announcementSubject }}</h2>
    <div style="line-height: 1.6;">
        {!! nl2br(e($announcementBody)) !!}
    </div>
    <hr style="margin: 24px 0; border: none; border-top: 1px solid #ebecef;" />
    <p style="font-size: 12px; color: #6a7283;">Sent by {{ config('app.name') }}</p>
</div>
