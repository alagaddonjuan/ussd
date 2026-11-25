@extends('layout.main')
@section('title', 'Platform Announcements — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 lg:mb-8">
        <h2 class="h2">Platform Broadcast</h2>
        <p class="text-sm text-n100 mt-1">Send an email announcement to all {{ $activeCount }} active client(s).</p>
    </div>

    <div class="box max-w-2xl border border-n30">
        <form method="POST" action="{{ route('admin.announcements.send') }}" class="space-y-4">
            @csrf
            <div>
                <label for="subject" class="block font-medium mb-2">Email Subject</label>
                <input type="text" name="subject" id="subject" required
                    class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                    placeholder="Scheduled Maintenance / Platform Update" />
            </div>
            <div>
                <label for="message" class="block font-medium mb-2">Announcement Message</label>
                <textarea name="message" id="message" rows="6" required
                    class="w-full rounded-2xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                    placeholder="Write your announcement here..."></textarea>
            </div>
            <button type="submit" class="btn-primary px-6">
                <i class="las la-bullhorn"></i>
                <span>Send Announcement</span>
            </button>
        </form>
    </div>
</div>
@endsection
