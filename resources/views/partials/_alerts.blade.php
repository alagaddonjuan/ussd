@if (session('status'))
    <div class="mb-6 flex items-center justify-between rounded-xl border border-primary/40 bg-primary/10 px-4 py-3 text-sm text-primary">
        <div class="flex items-center gap-2">
            <i class="las la-check-circle text-xl"></i>
            <span>{{ session('status') }}</span>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-xl border border-error/40 bg-error/10 px-4 py-3 text-sm text-error">
        <div class="flex items-center gap-2 font-semibold mb-1">
            <i class="las la-exclamation-triangle text-xl"></i>
            <span>Please review the following issue(s):</span>
        </div>
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
