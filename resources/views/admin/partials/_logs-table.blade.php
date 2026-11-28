@if ($tab === 'sms')
    <table class="w-full whitespace-nowrap text-sm">
        <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Client</th><th class="px-4 py-3 text-start">Phone</th><th class="px-4 py-3 text-start">Message</th><th class="px-4 py-3 text-start">Cost</th><th class="px-4 py-3 text-start">Status</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
        <tbody>
            @forelse ($smsLogs as $log)
                <tr class="even:bg-secondary/5">
                    <td class="px-4 py-2.5 font-medium">{{ $log->client?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2.5">{{ $log->phone_number }}</td>
                    <td class="px-4 py-2.5">{{ \Illuminate\Support\Str::limit($log->message, 40) }}</td>
                    <td class="px-4 py-2.5">₦{{ number_format((float) $log->cost, 2) }}</td>
                    <td class="px-4 py-2.5">{{ $log->status }}</td>
                    <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-n100">No SMS logs.</td></tr>
            @endforelse
        </tbody>
    </table>
@elseif ($tab === 'airtime')
    <table class="w-full whitespace-nowrap text-sm">
        <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Client</th><th class="px-4 py-3 text-start">Phone</th><th class="px-4 py-3 text-start">Network</th><th class="px-4 py-3 text-start">Amount</th><th class="px-4 py-3 text-start">Status</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
        <tbody>
            @forelse ($airtimeLogs as $log)
                <tr class="even:bg-secondary/5">
                    <td class="px-4 py-2.5 font-medium">{{ $log->client?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2.5">{{ $log->phone_number }}</td>
                    <td class="px-4 py-2.5">{{ $log->network }}</td>
                    <td class="px-4 py-2.5">₦{{ number_format((float) $log->amount, 2) }}</td>
                    <td class="px-4 py-2.5">{{ $log->status }}</td>
                    <td class="px-4 py-2.5 text-xs text-n100">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-n100">No airtime logs.</td></tr>
            @endforelse
        </tbody>
    </table>
@elseif ($tab === 'transactions')
    <table class="w-full whitespace-nowrap text-sm">
        <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Client</th><th class="px-4 py-3 text-start">Reference</th><th class="px-4 py-3 text-start">Type</th><th class="px-4 py-3 text-start">Gateway</th><th class="px-4 py-3 text-start">Amount</th><th class="px-4 py-3 text-start">Status</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
        <tbody>
            @forelse ($transactions as $tx)
                <tr class="even:bg-secondary/5">
                    <td class="px-4 py-2.5 font-medium">{{ $tx->client?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2.5 text-xs">{{ $tx->reference }}</td>
                    <td class="px-4 py-2.5 uppercase text-xs">{{ $tx->type }}</td>
                    <td class="px-4 py-2.5 uppercase text-xs">{{ $tx->gateway }}</td>
                    <td class="px-4 py-2.5">₦{{ number_format((float) $tx->amount, 2) }}</td>
                    <td class="px-4 py-2.5">{{ ucfirst($tx->status) }}</td>
                    <td class="px-4 py-2.5 text-xs text-n100">{{ optional($tx->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-n100">No transactions.</td></tr>
            @endforelse
        </tbody>
    </table>
@elseif ($tab === 'webhooks')
    <table class="w-full whitespace-nowrap text-sm">
        <thead>
            <tr class="bg-secondary/5">
                <th class="px-4 py-3 text-start">Provider</th>
                <th class="px-4 py-3 text-start">Endpoint</th>
                <th class="px-4 py-3 text-start">Status</th>
                <th class="px-4 py-3 text-start">Latency</th>
                <th class="px-4 py-3 text-start">Outcome</th>
                <th class="px-4 py-3 text-start">Session / Cost / Error</th>
                <th class="px-4 py-3 text-start">Time</th>
                <th class="px-4 py-3 text-start">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($webhookLogs as $wh)
                @php
                    $decoded = json_decode((string) $wh->payload, true);
                    $isOk = in_array($wh->outcome, ['ok', 'Success', 'charge.success', 'reconciled', 'replayed:ok', 'replayed:reconciled'], true);
                    $outcomeClass = $isOk
                        ? 'bg-[#10B981]/15 text-[#10B981]'
                        : (strtolower((string) $wh->outcome) === 'incomplete' ? 'bg-[#F59E0B]/15 text-[#F59E0B]' : 'bg-error/15 text-error');
                @endphp
                <tr class="even:bg-secondary/5">
                    <td class="px-4 py-2.5 font-medium uppercase text-xs">{{ $wh->provider }}</td>
                    <td class="px-4 py-2.5 text-xs">{{ $wh->endpoint }}</td>
                    <td class="px-4 py-2.5 text-xs">{{ $wh->status_code ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-xs">{{ $wh->latency_ms ? $wh->latency_ms . ' ms' : '—' }}</td>
                    <td class="px-4 py-2.5 text-xs">
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $outcomeClass }}">
                            {{ $wh->outcome }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs">
                        @if (is_array($decoded))
                            <div class="max-w-[260px] truncate" title="{{ $wh->payload }}">
                                {{ $decoded['serviceCode'] ?? '' }}
                                @if (!empty($decoded['phoneNumber'])) &bull; {{ $decoded['phoneNumber'] }} @endif
                                @if (!empty($decoded['cost'])) &bull; <strong class="text-primary">{{ $decoded['cost'] }}</strong> @endif
                            </div>
                            @if (!empty($decoded['errorMessage']) || !empty($decoded['error']))
                                <div class="text-[11px] text-error max-w-[260px] truncate" title="{{ $decoded['errorMessage'] ?? $decoded['error'] }}">
                                    {{ $decoded['errorMessage'] ?? $decoded['error'] }}
                                </div>
                            @elseif (!empty($decoded['sessionId']))
                                <div class="text-[11px] text-n100 font-mono truncate max-w-[260px]">{{ $decoded['sessionId'] }}</div>
                            @endif
                        @else
                            <span class="text-n100 max-w-[240px] truncate inline-block">{{ \Illuminate\Support\Str::limit($wh->payload, 50) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs text-n100">{{ optional($wh->created_at)->format('M d, H:i:s') }}</td>
                    <td class="px-4 py-2.5">
                        @if ($wh->provider === 'ussd_gateway')
                            <form method="POST" action="{{ route('admin.webhooks.replay', $wh) }}">
                                @csrf
                                <button type="submit" class="btn-outline py-1 px-2.5 text-[11px]">
                                    <i class="las la-redo-alt"></i> Replay
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-n100">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-n100">No webhook deliveries logged yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@elseif ($tab === 'audit')
    <table class="w-full whitespace-nowrap text-sm">
        <thead><tr class="bg-secondary/5"><th class="px-4 py-3 text-start">Actor</th><th class="px-4 py-3 text-start">Action</th><th class="px-4 py-3 text-start">Details</th><th class="px-4 py-3 text-start">IP</th><th class="px-4 py-3 text-start">Time</th></tr></thead>
        <tbody>
            @forelse ($auditLogs as $audit)
                <tr class="even:bg-secondary/5">
                    <td class="px-4 py-2.5 font-medium">{{ $audit->actor?->name ?? 'System' }}</td>
                    <td class="px-4 py-2.5 text-xs font-semibold text-primary">{{ $audit->action }}</td>
                    <td class="px-4 py-2.5 text-xs">{{ \Illuminate\Support\Str::limit($audit->details, 70) }}</td>
                    <td class="px-4 py-2.5 text-xs">{{ $audit->ip_address ?: '—' }}</td>
                    <td class="px-4 py-2.5 text-xs text-n100">{{ optional($audit->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-n100">No admin audit actions recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@else
    <table class="w-full text-xs sm:text-sm">
        <thead>
            <tr class="bg-secondary/5 text-[11px] uppercase tracking-wide text-n200">
                <th class="px-2.5 py-2.5 text-start">Client</th>
                <th class="px-2.5 py-2.5 text-start">Phone / Session</th>
                <th class="px-2.5 py-2.5 text-start">Carrier / Hops</th>
                <th class="px-2.5 py-2.5 text-start whitespace-nowrap">Ours</th>
                <th class="px-2.5 py-2.5 text-start whitespace-nowrap">AT Cost</th>
                <th class="px-2.5 py-2.5 text-start whitespace-nowrap">Margin</th>
                <th class="px-2.5 py-2.5 text-start">Status</th>
                <th class="px-2.5 py-2.5 text-end whitespace-nowrap">Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ussdLogs as $log)
                @php
                    $billed = $log->effectiveBilled();
                    $up = (float) ($log->upstream_cost ?? 0);
                    $margin = $log->margin();
                    $reconciled = $log->isReconciled();
                    $st = strtolower((string) ($log->status ?: 'success'));
                    $stClass = $st === 'success'
                        ? 'bg-[#10B981]/15 text-[#10B981]'
                        : ($st === 'incomplete' || str_contains($st, 'progress')
                            ? 'bg-[#F59E0B]/15 text-[#F59E0B]'
                            : 'bg-error/15 text-error');
                    $estAt = (float) ($log->estimated_at_cost ?? 0);
                @endphp
                <tr class="even:bg-secondary/5">
                    <td class="px-2.5 py-2.5">
                        <div class="font-medium leading-tight">{{ $log->client?->name ?? 'N/A' }}</div>
                        <div class="text-[11px] font-mono text-n100">{{ $log->service_code ?: '—' }}</div>
                    </td>
                    <td class="px-2.5 py-2.5">
                        <div class="font-medium whitespace-nowrap">{{ $log->phone_number }}</div>
                        <div class="text-[11px] font-mono text-n100 max-w-[130px] truncate" title="{{ $log->session_id }}">
                            {{ $log->session_id ?: 'no-session-id' }}
                        </div>
                    </td>
                    <td class="px-2.5 py-2.5 text-xs">
                        <div class="whitespace-nowrap">{{ $log->network_name ?: '—' }}</div>
                        <div class="text-[11px] text-n100 whitespace-nowrap">{{ $log->hops ?? 1 }} hop · {{ $log->duration_ms !== null ? round($log->duration_ms / 1000) . 's' : '—' }}</div>
                    </td>
                    <td class="px-2.5 py-2.5 font-semibold whitespace-nowrap {{ $billed <= 0 ? 'text-error' : '' }}">₦{{ number_format($billed, 2) }}</td>
                    <td class="px-2.5 py-2.5 text-xs whitespace-nowrap">
                        @if ($reconciled)
                            <div class="font-semibold">₦{{ number_format($up, 2) }}</div>
                            <span class="inline-block rounded-full bg-[#10B981]/12 px-2 py-0.5 text-[10px] font-semibold text-[#10B981]">AT Verified</span>
                        @else
                            <div class="font-semibold text-[#F59E0B]">₦{{ number_format($up, 2) }}</div>
                            <span class="inline-block rounded-full bg-[#F59E0B]/15 px-2 py-0.5 text-[10px] font-semibold text-[#F59E0B]"
                                title="End-of-session /ussd/events notification not yet received">
                                Awaiting AT (Est. ₦{{ number_format($estAt, 2) }})
                            </span>
                        @endif
                    </td>
                    <td class="px-2.5 py-2.5 text-xs font-semibold whitespace-nowrap {{ $margin >= 0 ? 'text-[#10B981]' : 'text-error' }}">
                        ₦{{ number_format($margin, 2) }}
                    </td>
                    <td class="px-2.5 py-2.5 text-xs">
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $stClass }}">
                            {{ $log->status ?: 'Success' }}
                        </span>
                        @if ($log->gateway_error)
                            <div class="text-[11px] text-error max-w-[155px] leading-snug mt-0.5 break-words">
                                {{ $log->gateway_error }}
                            </div>
                        @endif
                    </td>
                    <td class="px-2.5 py-2.5 text-end text-xs whitespace-nowrap">
                        <div class="text-n100">{{ optional($log->created_at)->format('M d, H:i') }}</div>
                        @if (! $reconciled || $billed <= 0)
                            <button type="button"
                                class="btn-outline py-0.5 px-2 mt-1 text-[10px] js-open-reconcile"
                                data-action="{{ route('admin.logs.reconcile-single', $log) }}"
                                data-session="{{ $log->session_id ?: ('Log #' . $log->id) }}"
                                data-phone="{{ $log->phone_number }}"
                                data-at-cost="{{ $up > 0 ? number_format($up, 2, '.', '') : number_format($estAt, 2, '.', '') }}"
                                data-status="{{ $log->status ?: 'Success' }}"
                                data-duration="{{ $log->duration_ms !== null ? (int) round($log->duration_ms / 1000) : '' }}">
                                Reconcile
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-n100">No USSD sessions matched your filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($ussdLogs->hasPages())
        <div class="mt-4">
            {{ $ussdLogs->links() }}
        </div>
    @endif
@endif
