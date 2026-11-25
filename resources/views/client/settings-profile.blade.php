@extends('layout.main')
@section('title', 'Profile Settings — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 lg:mb-8">
        <h2 class="h2">Profile Settings</h2>
    </div>

    <div class="box max-w-2xl border border-n30">
        <h4 class="h4 bb-dashed pb-4 mb-6">Account Details</h4>
        <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block font-medium mb-2">Company / Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}" required
                    class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" />
            </div>
            <div>
                <label class="block font-medium mb-2">Email Address</label>
                <input type="email" value="{{ $client->email }}" disabled
                    class="w-full rounded-3xl border border-n30 bg-secondary/5 px-4 py-3 text-sm text-n100" />
            </div>
            <div>
                <label for="sender_id" class="block font-medium mb-2">SMS Sender ID</label>
                <input type="text" name="sender_id" id="sender_id" value="{{ old('sender_id', $client->sender_id) }}"
                    class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" placeholder="Q4IComms" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label for="password" class="block font-medium mb-2">New Password (optional)</label>
                    <input type="password" name="password" id="password"
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" placeholder="Leave blank to keep current" />
                </div>
                <div>
                    <label for="password_confirmation" class="block font-medium mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" />
                </div>
            </div>
            <div class="pt-4">
                <button type="submit" class="btn-primary px-6">Save Profile</button>
            </div>
        </form>
    </div>
</div>
@endsection
