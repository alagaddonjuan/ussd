<?php

namespace App\Services;

use App\Models\Client;
use App\Models\UssdLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UssdReconciliationService
{
    public function __construct(
        protected UssdEngineService $engine
    ) {}

    /**
     * Parse Africa's Talking CSV export or pasted table rows into normalized session entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseInput(string $rawContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($rawContent)) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        if (empty($lines)) {
            return [];
        }

        $firstLine = $lines[0];
        $delimiter = str_contains($firstLine, "\t") ? "\t" : ',';
        $rows = [];

        foreach ($lines as $line) {
            if ($delimiter === "\t") {
                $cols = array_map('trim', explode("\t", $line));
            } else {
                $cols = array_map(fn ($v) => trim((string) $v), str_getcsv($line));
            }
            $rows[] = $cols;
        }

        $headerMap = $this->detectHeaderMap($rows[0]);
        $expectedCols = count($rows[0]);
        $startIndex = $headerMap !== null ? 1 : 0;

        if ($headerMap === null) {
            $expectedCols = 7;
            $headerMap = [
                'date' => 0,
                'service_code' => 1,
                'phone_number' => 2,
                'hops' => 3,
                'duration' => 4,
                'cost' => 5,
                'status' => 6,
            ];
        }

        $parsed = [];
        for ($i = $startIndex; $i < count($rows); $i++) {
            $cols = $this->repairSplitDateColumns($rows[$i], $expectedCols);
            if (count($cols) < 3) {
                continue;
            }

            $rawDate = $this->col($cols, $headerMap['date'] ?? null);
            $serviceCode = $this->col($cols, $headerMap['service_code'] ?? null);
            $phone = $this->col($cols, $headerMap['phone_number'] ?? null);
            $rawHops = $this->col($cols, $headerMap['hops'] ?? null);
            $rawDuration = $this->col($cols, $headerMap['duration'] ?? null);
            $rawCost = $this->col($cols, $headerMap['cost'] ?? null);
            $status = $this->col($cols, $headerMap['status'] ?? null);
            $sessionId = $this->col($cols, $headerMap['session_id'] ?? null);
            $network = $this->col($cols, $headerMap['network'] ?? null);
            $errorMsg = $this->col($cols, $headerMap['error'] ?? null);

            $atCost = $this->engine->parseAtCost($rawCost);
            if ($phone === '' && $serviceCode === '' && $atCost === null) {
                continue;
            }

            $parsed[] = [
                'row_num' => $i + 1,
                'raw_date' => $rawDate,
                'timestamp' => $this->parseDate($rawDate),
                'service_code' => $serviceCode,
                'phone_number' => $this->normalizePhoneDisplay($phone),
                'phone_key' => $this->phoneKey($phone),
                'hops' => is_numeric($rawHops) ? max(1, (int) $rawHops) : 1,
                'duration_ms' => $this->parseDurationMs($rawDuration),
                'raw_cost' => $rawCost,
                'at_cost' => $atCost ?? 0.0,
                'status' => $status !== '' ? $status : 'Success',
                'session_id' => $sessionId,
                'network' => $network,
                'error_message' => $errorMsg,
            ];
        }

        return $parsed;
    }

    /**
     * Compare parsed Africa's Talking rows against local ussd_logs.
     *
     * @param  array<int, array<string, mixed>>  $parsedRows
     * @return array<string, mixed>
     */
    public function analyze(array $parsedRows): array
    {
        $clientsByCode = Client::query()
            ->with('pricingTier')
            ->whereNotNull('ussd_code')
            ->where('ussd_code', '!=', '')
            ->get()
            ->keyBy(fn (Client $c) => trim((string) $c->ussd_code));

        $candidateLogs = UssdLog::query()
            ->with('client')
            ->orderByDesc('id')
            ->limit(2000)
            ->get();

        $usedLogIds = [];
        $items = [];

        $totals = [
            'at_rows' => count($parsedRows),
            'at_cost' => 0.0,
            'expected_billed' => 0.0,
            'local_upstream' => 0.0,
            'local_billed' => 0.0,
            'unbilled_delta' => 0.0,
            'matched_ok' => 0,
            'mismatch' => 0,
            'missing_local' => 0,
            'unknown_client' => 0,
        ];

        foreach ($parsedRows as $idx => $row) {
            $client = $clientsByCode->get(trim((string) $row['service_code']));
            $atCost = round((float) $row['at_cost'], 4);
            $expectedCharge = $client ? round($atCost * $client->getUssdCostMultiplier(), 2) : 0.0;

            $totals['at_cost'] += $atCost;
            $totals['expected_billed'] += $expectedCharge;

            $matched = $this->findMatchingLocalLog($row, $client, $candidateLogs, $usedLogIds);

            if (! $client) {
                $state = 'unknown_client';
                $totals['unknown_client']++;
                $localUpstream = 0.0;
                $localBilled = 0.0;
                $delta = 0.0;
            } elseif (! $matched) {
                $state = 'missing_local';
                $totals['missing_local']++;
                $localUpstream = 0.0;
                $localBilled = 0.0;
                $delta = $expectedCharge;
            } else {
                $usedLogIds[$matched->id] = true;
                $localUpstream = round((float) ($matched->upstream_cost ?? 0), 4);
                $localBilled = $matched->effectiveBilled();
                $delta = round($expectedCharge - $localBilled, 2);

                $upstreamMismatch = abs($localUpstream - $atCost) > 0.01;
                $billedMismatch = abs($delta) > 0.01;
                $unreconciled = ! $matched->isReconciled();

                if ($upstreamMismatch || $billedMismatch || $unreconciled) {
                    $state = 'mismatch';
                    $totals['mismatch']++;
                } else {
                    $state = 'matched_ok';
                    $totals['matched_ok']++;
                }
            }

            $totals['local_upstream'] += $localUpstream;
            $totals['local_billed'] += $localBilled;
            $totals['unbilled_delta'] += max(0.0, $delta);

            $items[] = [
                'index' => $idx,
                'at' => $row,
                'client' => $client,
                'log' => $matched,
                'state' => $state,
                'expected_billed' => $expectedCharge,
                'local_upstream' => $localUpstream,
                'local_billed' => $localBilled,
                'delta' => $delta,
            ];
        }

        $totals['at_cost'] = round($totals['at_cost'], 2);
        $totals['expected_billed'] = round($totals['expected_billed'], 2);
        $totals['local_upstream'] = round($totals['local_upstream'], 2);
        $totals['local_billed'] = round($totals['local_billed'], 2);
        $totals['unbilled_delta'] = round($totals['unbilled_delta'], 2);

        return [
            'totals' => $totals,
            'items' => $items,
        ];
    }

    /**
     * Backfill missing sessions and reconcile cost/status mismatches against AT records.
     *
     * @param  array<int, array<string, mixed>>  $parsedRows
     * @return array{created: int, updated: int, skipped: int, total_delta_charged: float, total_at_cost: float}
     */
    public function apply(array $parsedRows): array
    {
        $analysis = $this->analyze($parsedRows);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $totalDeltaCharged = 0.0;
        $totalAtCost = 0.0;

        foreach ($analysis['items'] as $item) {
            $state = $item['state'];
            /** @var Client|null $client */
            $client = $item['client'];
            /** @var UssdLog|null $log */
            $log = $item['log'];
            $at = $item['at'];

            if (! $client || $state === 'matched_ok' || $state === 'unknown_client') {
                $skipped++;

                continue;
            }

            $atCost = round((float) $at['at_cost'], 4);
            $expectedCharge = round((float) $item['expected_billed'], 2);
            $totalAtCost += $atCost;

            DB::transaction(function () use (
                $state,
                $client,
                $log,
                $at,
                $atCost,
                $expectedCharge,
                &$created,
                &$updated,
                &$skipped,
                &$totalDeltaCharged
            ): void {
                $lockedClient = Client::query()->whereKey($client->id)->lockForUpdate()->first();
                if (! $lockedClient) {
                    $skipped++;

                    return;
                }

                if ($state === 'missing_local') {
                    $syntheticSessionId = trim((string) ($at['session_id'] ?? ''));
                    if ($syntheticSessionId === '') {
                        $fingerprint = implode('|', [
                            $lockedClient->id,
                            $at['service_code'],
                            $at['phone_key'],
                            $at['raw_date'],
                            $at['hops'],
                            (string) ($at['duration_ms'] ?? ''),
                            (string) $atCost,
                            (string) $at['row_num'],
                        ]);
                        $syntheticSessionId = 'AT-CSV-'.substr(md5($fingerprint), 0, 20);
                    }

                    $alreadyImported = UssdLog::query()
                        ->where('client_id', $lockedClient->id)
                        ->where('session_id', $syntheticSessionId)
                        ->exists();

                    if ($alreadyImported) {
                        $skipped++;

                        return;
                    }

                    if ($expectedCharge > 0) {
                        $lockedClient->decrement('token_balance', $expectedCharge);
                        $totalDeltaCharged += $expectedCharge;
                    }

                    $networkName = $at['network'] !== ''
                        ? $at['network']
                        : $this->engine->resolveNetworkName('', (string) $at['phone_number']);
                    $createdAt = $at['timestamp'] ?? now();

                    UssdLog::create([
                        'client_id' => $lockedClient->id,
                        'phone_number' => $at['phone_number'],
                        'session_id' => $syntheticSessionId,
                        'service_code' => $at['service_code'] ?: (string) $lockedClient->ussd_code,
                        'session_data' => 'Verified via AT CSV',
                        'cost' => $expectedCharge,
                        'client_price' => $expectedCharge,
                        'upstream_cost' => $atCost,
                        'duration_ms' => $at['duration_ms'],
                        'hops' => $at['hops'],
                        'network_name' => $networkName !== 'Unknown' ? $networkName : null,
                        'status' => $at['status'] ?: 'Success',
                        'gateway_error' => $at['error_message'] !== '' ? $at['error_message'] : null,
                        'created_at' => $createdAt,
                        'logged_at' => $createdAt,
                        'reconciled_at' => now(),
                    ]);

                    $created++;

                    return;
                }

                if ($log) {
                    $lockedLog = UssdLog::query()->whereKey($log->id)->lockForUpdate()->first();
                    if (! $lockedLog) {
                        $skipped++;

                        return;
                    }

                    $alreadyBilled = $lockedLog->effectiveBilled();
                    $delta = round($expectedCharge - $alreadyBilled, 2);

                    if ($delta > 0) {
                        $lockedClient->decrement('token_balance', $delta);
                        $totalDeltaCharged += $delta;
                    } elseif ($delta < 0 && $alreadyBilled > 0) {
                        $lockedClient->increment('token_balance', abs($delta));
                        $totalDeltaCharged += $delta;
                    }

                    $lockedLog->update([
                        'cost' => $expectedCharge,
                        'client_price' => $expectedCharge,
                        'upstream_cost' => $atCost,
                        'duration_ms' => $at['duration_ms'] ?? $lockedLog->duration_ms,
                        'hops' => max((int) $at['hops'], (int) ($lockedLog->hops ?? 1)),
                        'status' => $at['status'] ?: $lockedLog->status,
                        'gateway_error' => $at['error_message'] !== '' ? $at['error_message'] : $lockedLog->gateway_error,
                        'reconciled_at' => now(),
                    ]);

                    $updated++;
                }
            });
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total_delta_charged' => round($totalDeltaCharged, 2),
            'total_at_cost' => round($totalAtCost, 2),
        ];
    }

    /**
     * Reconcile and charge a single UssdLog record against a known Africa's Talking cost.
     *
     * @return array{client_charge: float, delta: float, at_cost: float}
     */
    public function reconcileSingleLog(
        UssdLog $log,
        float $atCost,
        ?string $status = null,
        ?int $durationMs = null,
        ?int $hops = null
    ): array {
        $atCost = round(max(0.0, $atCost), 4);
        $result = ['client_charge' => 0.0, 'delta' => 0.0, 'at_cost' => $atCost];

        DB::transaction(function () use ($log, $atCost, $status, $durationMs, $hops, &$result): void {
            $lockedLog = UssdLog::query()->whereKey($log->id)->lockForUpdate()->firstOrFail();
            $lockedClient = Client::query()->whereKey($lockedLog->client_id)->lockForUpdate()->firstOrFail();

            $clientCharge = round($atCost * $lockedClient->getUssdCostMultiplier(), 2);
            $alreadyBilled = $lockedLog->effectiveBilled();
            $delta = round($clientCharge - $alreadyBilled, 2);

            if ($delta > 0) {
                $lockedClient->decrement('token_balance', $delta);
            } elseif ($delta < 0 && $alreadyBilled > 0) {
                $lockedClient->increment('token_balance', abs($delta));
            }

            $lockedLog->update([
                'cost' => $clientCharge,
                'client_price' => $clientCharge,
                'upstream_cost' => $atCost,
                'duration_ms' => $durationMs ?? $lockedLog->duration_ms,
                'hops' => $hops ?? $lockedLog->hops,
                'status' => ($status !== null && $status !== '') ? $status : ($lockedLog->status === 'In Progress' ? 'Success' : $lockedLog->status),
                'reconciled_at' => now(),
            ]);

            $result = [
                'client_charge' => $clientCharge,
                'delta' => $delta,
                'at_cost' => $atCost,
            ];
        });

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UssdLog>  $candidates
     * @param  array<int, bool>  $usedLogIds
     */
    protected function findMatchingLocalLog(array $row, ?Client $client, $candidates, array $usedLogIds): ?UssdLog
    {
        $sessionId = trim((string) ($row['session_id'] ?? ''));
        if ($sessionId !== '') {
            $bySession = $candidates->first(function (UssdLog $log) use ($sessionId, $usedLogIds) {
                return ! isset($usedLogIds[$log->id]) && trim((string) $log->session_id) === $sessionId;
            });
            if ($bySession) {
                return $bySession;
            }
        }

        if (! $client) {
            return null;
        }

        $phoneKey = (string) $row['phone_key'];
        if ($phoneKey === '') {
            return null;
        }

        $matches = $candidates->filter(function (UssdLog $log) use ($client, $phoneKey, $usedLogIds) {
            if (isset($usedLogIds[$log->id])) {
                return false;
            }
            if ((int) $log->client_id !== (int) $client->id) {
                return false;
            }

            return $this->phoneKey((string) $log->phone_number) === $phoneKey;
        });

        if ($matches->isEmpty()) {
            return null;
        }

        $targetHops = (int) $row['hops'];
        $targetDur = $row['duration_ms'];
        $targetTs = $row['timestamp'];

        return $matches->sortBy(function (UssdLog $log) use ($targetHops, $targetDur, $targetTs) {
            $hopsDiff = abs((int) ($log->hops ?? 1) - $targetHops);
            $durDiff = ($targetDur !== null && $log->duration_ms !== null)
                ? abs((int) $log->duration_ms - (int) $targetDur)
                : 5000;

            $timeDiff = 999999;
            if ($targetTs && $log->created_at) {
                $rawDiff = abs($log->created_at->diffInSeconds($targetTs));
                // Account for dashboard vs server timezone offsets (up to ±12h mod 3600s)
                $modDiff = min($rawDiff % 3600, 3600 - ($rawDiff % 3600));
                $timeDiff = $rawDiff <= 900 ? $rawDiff : ($rawDiff <= 43200 ? 1000 + $modDiff : $rawDiff);
            }

            return [
                $hopsDiff <= 1 ? 0 : 1,
                $durDiff <= 5000 ? 0 : 1,
                $hopsDiff,
                $durDiff,
                $timeDiff,
            ];
        })->first();
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<string, int>|null
     */
    protected function detectHeaderMap(array $headerRow): ?array
    {
        $map = [];
        foreach ($headerRow as $idx => $cell) {
            $norm = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($cell))) ?? '';
            $norm = trim($norm, '_');

            if (in_array($norm, ['date', 'time', 'created_at', 'timestamp'], true)) {
                $map['date'] = $idx;
            } elseif (in_array($norm, ['service_code', 'servicecode', 'code', 'shortcode', 'ussd_code'], true)) {
                $map['service_code'] = $idx;
            } elseif (in_array($norm, ['phone_number', 'phonenumber', 'phone', 'msisdn'], true)) {
                $map['phone_number'] = $idx;
            } elseif (in_array($norm, ['hops', 'hop', 'hops_count', 'hopscount'], true)) {
                $map['hops'] = $idx;
            } elseif (in_array($norm, ['duration', 'duration_in_millis', 'durationinmillis', 'dur'], true)) {
                $map['duration'] = $idx;
            } elseif (in_array($norm, ['cost', 'upstream_cost', 'at_cost', 'amount'], true)) {
                $map['cost'] = $idx;
            } elseif ($norm === 'status') {
                $map['status'] = $idx;
            } elseif (in_array($norm, ['session_id', 'sessionid'], true)) {
                $map['session_id'] = $idx;
            } elseif (in_array($norm, ['network', 'carrier', 'network_name', 'networkcode'], true)) {
                $map['network'] = $idx;
            } elseif (in_array($norm, ['error', 'error_message', 'errormessage'], true)) {
                $map['error'] = $idx;
            }
        }

        if (isset($map['phone_number']) && (isset($map['cost']) || isset($map['status']) || isset($map['hops']))) {
            return $map;
        }

        return null;
    }

    /**
     * Rejoin unquoted "Month DD, YYYY ..." date cells split on the comma inside CSV rows.
     *
     * @param  array<int, string>  $cols
     * @return array<int, string>
     */
    protected function repairSplitDateColumns(array $cols, int $expectedCols): array
    {
        if (
            count($cols) > $expectedCols
            && isset($cols[0], $cols[1])
            && preg_match('/^[A-Za-z]+\s+\d{1,2}$/', $cols[0])
            && preg_match('/^\d{4}\b/', $cols[1])
        ) {
            $merged = $cols[0].', '.$cols[1];

            return array_values(array_merge([$merged], array_slice($cols, 2)));
        }

        return $cols;
    }

    /**
     * @param  array<int, string>  $cols
     */
    protected function col(array $cols, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $cols)) {
            return '';
        }

        return trim((string) $cols[$index]);
    }

    protected function parseDate(string $rawDate): ?Carbon
    {
        if ($rawDate === '') {
            return null;
        }

        try {
            return Carbon::parse($rawDate);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseDurationMs(string $raw): ?int
    {
        $clean = strtolower(trim($raw));
        if ($clean === '' || $clean === '—' || $clean === '-') {
            return null;
        }

        if (preg_match('/^(\d+)\s*ms$/', $clean, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/^(\d+(?:\.\d+)?)\s*s$/', $clean, $m)) {
            return (int) round(((float) $m[1]) * 1000);
        }
        if (preg_match('/^(\d+):(\d{2})$/', $clean, $m)) {
            return ((int) $m[1] * 60 + (int) $m[2]) * 1000;
        }
        if (is_numeric($clean)) {
            $val = (float) $clean;

            return $val > 500 ? (int) round($val) : (int) round($val * 1000);
        }

        return null;
    }

    protected function normalizePhoneDisplay(string $phone): string
    {
        $trimmed = trim($phone);
        if ($trimmed === '') {
            return '';
        }
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '+234'.substr($digits, 1);
        }
        if (strlen($digits) === 13 && str_starts_with($digits, '234')) {
            return '+'.$digits;
        }

        return $trimmed;
    }

    protected function phoneKey(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) > 10) {
            return substr($digits, -10);
        }

        return $digits;
    }
}
