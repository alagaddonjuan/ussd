@forelse ($ussdLogs as $log)
    @php
        $st = strtolower((string) ($log->status ?: 'success'));
        $stClass = $st === 'success'
            ? 'bg-[#10B981]/15 text-[#10B981]'
            : ($st === 'incomplete' || str_contains($st, 'progress')
                ? 'bg-[#F59E0B]/15 text-[#F59E0B]'
                : 'bg-error/15 text-error');
    @endphp
    <tr class="even:bg-secondary/5">
        <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('M d, Y h:i A') }}</td>
        <td class="px-4 py-2.5">{{ $log->service_code }}</td>
        <td class="px-4 py-2.5 font-medium">{{ $log->phone_number }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $log->network_name ?: '—' }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $log->hops ?? 1 }}</td>
        <td class="px-4 py-2.5 text-xs">{{ $log->duration_ms !== null ? round($log->duration_ms / 1000) . 's' : '—' }}</td>
        <td class="px-4 py-2.5">{{ \Illuminate\Support\Str::limit($log->session_data, 28) }}</td>
        <td class="px-4 py-2.5 font-semibold">₦{{ number_format((float) ($log->client_price ?? $log->cost ?? 0), 2) }}</td>
        <td class="px-4 py-2.5">
            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stClass }}">
                {{ ucfirst($log->status ?: 'Success') }}
            </span>
        </td>
    </tr>
@empty
    <tr><td colspan="9" class="px-4 py-6 text-center text-n100">No USSD logs recorded yet.</td></tr>
@endforelse
