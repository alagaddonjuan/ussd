@forelse ($recentUssdLogs as $log)
    <tr class="even:bg-secondary/5">
        <td class="px-4 py-2.5 font-medium">{{ $log->phone_number }}</td>
        <td class="px-4 py-2.5">{{ \Illuminate\Support\Str::limit($log->session_data ?: 'Main Menu', 28) }}</td>
        <td class="px-4 py-2.5 font-semibold">₦{{ number_format((float) ($log->client_price ?? $log->cost ?? 0), 2) }}</td>
        <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('M d, H:i') }}</td>
        <td class="px-4 py-2.5 text-end">
            <button type="button"
                class="btn-outline py-1 px-2 text-xs js-view-ussd-details"
                data-details-url="{{ route('ussd.logs.details', $log) }}"
                title="View session details and hops">
                <i class="las la-ellipsis-h"></i>
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-4 py-6 text-center text-n100">No USSD sessions logged yet.</td>
    </tr>
@endforelse
