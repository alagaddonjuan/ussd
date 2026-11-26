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
            <div>
                <label for="low_balance_threshold" class="block font-medium mb-2">Low Wallet Balance Alert Threshold (₦)</label>
                <input type="number" step="100" min="0" name="low_balance_threshold" id="low_balance_threshold"
                    value="{{ old('low_balance_threshold', $client->low_balance_threshold ?? 5000) }}"
                    class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" />
                <p class="text-xs text-n100 mt-1">We email you automatically whenever your balance drops below this amount (set 0 to disable).</p>
            </div>
            <div class="rounded-2xl border border-n30 bg-primary/5 p-4 text-xs">
                <p class="font-semibold text-sm mb-1">KYC Status: <span class="text-primary">{{ ucfirst($client->kyc_status ?? 'unverified') }}</span></p>
                @if ($client->kyc_notes)
                    <p class="text-n100 mb-1">Reviewer Notes: {{ $client->kyc_notes }}</p>
                @endif
                <p class="text-n100">Submit Certificate of Incorporation, Request Letter, and Valid ID to <strong>support@q4iltd.com</strong> for dedicated shortcode activation.</p>
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

    <div id="kyc" class="box max-w-2xl border border-n30 mt-6">
        <div class="bb-dashed pb-4 mb-5 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h4 class="h4">KYC &amp; Dedicated Shortcode Documents</h4>
                <p class="text-xs text-n100 mt-1">Upload your CAC Certificate, Request Letter, or Government ID (PDF, JPG, PNG up to 5 MB).</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ ($client->kyc_status ?? 'unverified') === 'approved' ? 'bg-[#10B981]/15 text-[#10B981]' : 'bg-warning/15 text-warning' }}">
                {{ ucfirst($client->kyc_status ?? 'unverified') }}
            </span>
        </div>

        @if ($client->kyc_document_path)
            <div class="mb-4 rounded-2xl border border-[#10B981]/30 bg-[#10B981]/10 px-4 py-3 text-xs flex flex-wrap items-center justify-between gap-2">
                <span>
                    <i class="las la-file-alt text-base text-[#10B981]"></i>
                    Latest Submission: <strong>{{ basename($client->kyc_document_path) }}</strong>
                    @if ($client->kyc_submitted_at)
                        ({{ $client->kyc_submitted_at->format('M d, Y H:i') }})
                    @endif
                </span>
                @if ($client->kyc_notes)
                    <span class="text-n100">Note: {{ $client->kyc_notes }}</span>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('settings.kyc.upload') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="kyc_document" class="block font-medium mb-2 text-sm">KYC Verification Pack (PDF / Image)</label>
                <input type="file" name="kyc_document" id="kyc_document" accept=".pdf,.jpg,.jpeg,.png" required
                    class="w-full rounded-2xl border border-n30 bg-primary/5 px-4 py-2.5 text-sm" />
            </div>
            <div>
                <label for="kyc_notes_input" class="block font-medium mb-2 text-sm">Reference / Business Notes (optional)</label>
                <input type="text" name="kyc_notes" id="kyc_notes_input" value="{{ old('kyc_notes', $client->kyc_notes) }}"
                    class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-2.5 text-sm"
                    placeholder="e.g., RC Number or Requested Shortcode *384*XX#" />
            </div>
            <button type="submit" class="btn-secondary px-6">
                <i class="las la-cloud-upload-alt"></i> Upload KYC Document
            </button>
        </form>
    </div>
</div>
@endsection
