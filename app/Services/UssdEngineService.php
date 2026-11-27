<?php

namespace App\Services;

use App\Models\Client;
use App\Models\UssdLog;
use App\Models\UssdMenu;
use App\Models\UssdMenuItem;
use App\Models\UssdPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UssdEngineService
{
    protected ?string $lastGatewayError = null;

    protected ?string $lastCallbackOutcome = null;

    public function getLastGatewayError(): ?string
    {
        return $this->lastGatewayError;
    }

    public function getLastCallbackOutcome(): ?string
    {
        return $this->lastCallbackOutcome;
    }

    /**
     * Parse raw Africa's Talking USSD text while honoring "0" as back navigation.
     *
     * @return array<int, string>
     */
    public function parseInputs(string $rawText): array
    {
        if ($rawText === '') {
            return [];
        }

        $inputs = [];
        foreach (explode('*', $rawText) as $segment) {
            $cleaned = trim($segment);
            if ($cleaned === '0') {
                array_pop($inputs);
            } elseif ($cleaned !== '') {
                $inputs[] = $cleaned;
            }
        }

        return $inputs;
    }

    /**
     * Handle an incoming USSD callback payload or simulator request.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(array $payload, bool $billClient = true): string
    {
        $this->lastGatewayError = null;
        $this->lastCallbackOutcome = 'ok';

        $sessionId = trim((string) ($payload['sessionId'] ?? ''));
        $serviceCode = trim((string) ($payload['serviceCode'] ?? ''));
        $phoneNumber = trim((string) ($payload['phoneNumber'] ?? ''));
        $rawText = (string) ($payload['text'] ?? '');
        $networkCode = trim((string) ($payload['networkCode'] ?? ''));

        $inputs = $this->parseInputs($rawText);
        $cleanedText = implode('*', $inputs);

        $client = Client::query()->where('ussd_code', $serviceCode)->first();
        if (! $client) {
            $this->lastCallbackOutcome = 'rejected:unknown_code';
            $this->lastGatewayError = "Unrecognized USSD code: {$serviceCode}";

            return 'END Error: USSD Code not recognized.';
        }

        if ($client->status === 'suspended') {
            $this->lastCallbackOutcome = 'rejected:suspended';
            $this->lastGatewayError = 'Client account is suspended.';
            $reply = 'END Error: Client account is suspended.';
            if ($billClient) {
                $this->billAndLogSession($client, $networkCode, $phoneNumber, $sessionId, $serviceCode, $cleanedText, $rawText, $payload, 'Failed', $this->lastGatewayError, $reply, false);
            }

            return $reply;
        }

        $menu = UssdMenu::query()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('is_archived', false)->orWhereNull('is_archived'))
            ->first();

        if (! $menu) {
            $this->lastCallbackOutcome = 'rejected:no_menu';
            $this->lastGatewayError = 'No active menu found.';
            $reply = 'END Error: No active menu found.';
            if ($billClient) {
                $this->billAndLogSession($client, $networkCode, $phoneNumber, $sessionId, $serviceCode, $cleanedText, $rawText, $payload, 'Failed', $this->lastGatewayError, $reply, true);
            }

            return $reply;
        }

        if ($billClient && $rawText === '' && (float) $client->token_balance <= 0) {
            $hasExistingSession = $sessionId !== '' && UssdLog::query()
                ->where('client_id', $client->id)
                ->where('session_id', $sessionId)
                ->exists();

            if (! $hasExistingSession) {
                $this->lastCallbackOutcome = 'rejected:low_balance';
                $this->lastGatewayError = 'Insufficient wallet balance.';
                $reply = 'END Service unavailable: Insufficient wallet balance.';
                $this->billAndLogSession($client, $networkCode, $phoneNumber, $sessionId, $serviceCode, $cleanedText, $rawText, $payload, 'Rejected', $this->lastGatewayError, $reply, true);

                return $reply;
            }
        }

        $reply = $this->resolveMenuResponse($menu, $inputs, $sessionId, $serviceCode, $phoneNumber, $cleanedText);

        if ($this->lastGatewayError !== null) {
            $this->lastCallbackOutcome = 'upstream_error';
            $initialStatus = 'Failed';
        } elseif (str_starts_with($reply, 'END Error:')) {
            $this->lastCallbackOutcome = 'rejected';
            $initialStatus = 'Failed';
        } elseif (str_starts_with($reply, 'END ')) {
            $this->lastCallbackOutcome = 'ok';
            $initialStatus = 'Success';
        } else {
            $this->lastCallbackOutcome = 'ok';
            $initialStatus = 'In Progress';
        }

        if ($billClient) {
            $this->billAndLogSession($client, $networkCode, $phoneNumber, $sessionId, $serviceCode, $cleanedText, $rawText, $payload, $initialStatus, $this->lastGatewayError, $reply, true);
        }

        return $reply;
    }

    /**
     * Parse an Africa's Talking cost string (e.g. "NGN 3.75" or "4.00") into a float.
     */
    public function parseAtCost(mixed $rawCost): ?float
    {
        if ($rawCost === null || $rawCost === '') {
            return null;
        }

        if (is_numeric($rawCost)) {
            $value = (float) $rawCost;

            return $value >= 0 ? $value : null;
        }

        if (preg_match('/(\d+(?:,\d{3})*(?:\.\d+)?)/', (string) $rawCost, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return null;
    }

    /**
     * Estimate per-interval Africa's Talking upstream cost for Nigerian networks.
     */
    public function estimateAtUpstreamCost(string $networkCode = '', string $networkNameOrPhone = ''): float
    {
        $code = trim($networkCode);
        if ($code === '62130') {
            return 7.50;
        }
        if ($code === '62120') {
            return 4.89;
        }
        if ($code === '62150') {
            return 12.00;
        }
        if ($code === '62160') {
            return 4.50;
        }

        $label = strtolower($this->resolveNetworkName($code, $networkNameOrPhone));
        if (str_contains($label, 'mtn')) {
            return 7.50;
        }
        if (str_contains($label, 'airtel')) {
            return 4.89;
        }
        if (str_contains($label, 'glo')) {
            return 12.00;
        }
        if (str_contains($label, '9mobile') || str_contains($label, 't2')) {
            return 4.50;
        }

        return (float) config('services.ussd.default_cost', 3.50);
    }

    /**
     * Reconcile final session cost reported by Africa's Talking /events webhook using client tier USSD multiplier.
     *
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, outcome: string, reason: ?string, ussd_log_id: ?int}
     */
    public function reconcileEventCost(array $payload): array
    {
        $atCost = $this->parseAtCost($payload['cost'] ?? $payload['at_cost'] ?? null);
        $sessionId = trim((string) ($payload['sessionId'] ?? ''));
        $serviceCode = trim((string) ($payload['serviceCode'] ?? ''));
        $phoneNumber = trim((string) ($payload['phoneNumber'] ?? ''));
        $networkCode = trim((string) ($payload['networkCode'] ?? ''));
        $durationMs = isset($payload['durationInMillis']) && is_numeric($payload['durationInMillis'])
            ? max(0, (int) $payload['durationInMillis'])
            : null;
        $explicitHops = isset($payload['hopsCount']) && is_numeric($payload['hopsCount'])
            ? max(1, (int) $payload['hopsCount'])
            : null;
        $status = trim((string) ($payload['status'] ?? ''));
        $rawText = (string) ($payload['text'] ?? ($payload['input'] ?? ''));
        $errorMessage = trim((string) ($payload['errorMessage'] ?? ($payload['error_message'] ?? '')));
        $lastAppResponse = trim((string) ($payload['lastAppResponse'] ?? ''));
        $networkName = $this->resolveNetworkName($networkCode, $phoneNumber);

        $client = $serviceCode !== ''
            ? Client::query()->where('ussd_code', $serviceCode)->first()
            : null;

        if (! $client && $sessionId !== '') {
            $existingLog = UssdLog::query()->where('session_id', $sessionId)->orderByDesc('id')->first();
            if ($existingLog && $existingLog->client_id) {
                $client = Client::find($existingLog->client_id);
                $serviceCode = $serviceCode !== '' ? $serviceCode : (string) $existingLog->service_code;
                $phoneNumber = $phoneNumber !== '' ? $phoneNumber : (string) $existingLog->phone_number;
            }
        }

        if (! $client) {
            Log::warning('USSD event cost received for unknown client', [
                'sessionId' => $sessionId,
                'serviceCode' => $serviceCode,
                'atCost' => $atCost,
                'errorMessage' => $errorMessage,
            ]);

            return [
                'ok' => false,
                'outcome' => 'error:unknown_client',
                'reason' => "Unknown client for serviceCode '{$serviceCode}' (sessionId '{$sessionId}')",
                'ussd_log_id' => null,
            ];
        }

        $clientCharge = $atCost !== null
            ? round($atCost * $client->getUssdCostMultiplier(), 2)
            : null;

        $logId = null;

        DB::transaction(function () use (
            $client,
            $clientCharge,
            $atCost,
            $sessionId,
            $serviceCode,
            $phoneNumber,
            $status,
            $rawText,
            $durationMs,
            $explicitHops,
            $networkName,
            $errorMessage,
            $lastAppResponse,
            &$logId
        ): void {
            $lockedClient = Client::query()->whereKey($client->id)->lockForUpdate()->first();
            if (! $lockedClient) {
                return;
            }

            if ($sessionId !== '') {
                $sessionLogs = UssdLog::query()
                    ->where('session_id', $sessionId)
                    ->where('client_id', $lockedClient->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($sessionLogs->isNotEmpty()) {
                    $alreadyBilled = round((float) $sessionLogs->sum('cost'), 2);

                    if ($clientCharge !== null) {
                        $delta = round($clientCharge - $alreadyBilled, 2);
                        if ($delta > 0) {
                            $lockedClient->decrement('token_balance', $delta);
                        } elseif ($delta < 0 && $alreadyBilled > 0) {
                            $lockedClient->increment('token_balance', abs($delta));
                        }
                    }

                    $lastLog = $sessionLogs->last();
                    $mergedHops = $explicitHops ?? max((int) $sessionLogs->count(), (int) ($sessionLogs->max('hops') ?? 1));

                    $finalCost = $clientCharge ?? round((float) $lastLog->cost, 2);
                    $finalClientPrice = $clientCharge ?? round((float) ($lastLog->client_price ?? $lastLog->cost), 2);
                    $finalUpstream = $atCost ?? round((float) ($lastLog->upstream_cost ?? 0), 4);
                    $resolvedStatus = $status !== ''
                        ? $status
                        : ($errorMessage !== '' ? 'Failed' : ($lastLog->status && $lastLog->status !== 'In Progress' ? $lastLog->status : 'Success'));

                    $lastLog->update([
                        'cost' => $finalCost,
                        'client_price' => $finalClientPrice,
                        'upstream_cost' => $finalUpstream,
                        'duration_ms' => $durationMs ?? $lastLog->duration_ms,
                        'hops' => $mergedHops,
                        'network_name' => $networkName !== 'Unknown' ? $networkName : $lastLog->network_name,
                        'final_user_string' => $rawText !== '' ? $rawText : $lastLog->final_user_string,
                        'status' => $resolvedStatus,
                        'gateway_error' => $errorMessage !== '' ? $errorMessage : $lastLog->gateway_error,
                        'last_app_response' => $lastAppResponse !== '' ? $lastAppResponse : $lastLog->last_app_response,
                        'reconciled_at' => now(),
                    ]);

                    if ($sessionLogs->count() > 1) {
                        UssdLog::query()
                            ->where('session_id', $sessionId)
                            ->where('client_id', $lockedClient->id)
                            ->where('id', '!=', $lastLog->id)
                            ->delete();
                    }

                    $logId = $lastLog->id;

                    return;
                }
            }

            $finalCharge = $clientCharge ?? 0.0;
            if ($finalCharge > 0) {
                $lockedClient->decrement('token_balance', $finalCharge);
            }

            $resolvedStatus = $status !== '' ? $status : ($errorMessage !== '' ? 'Failed' : 'Success');

            $created = UssdLog::create([
                'client_id' => $lockedClient->id,
                'phone_number' => $phoneNumber,
                'session_id' => $sessionId,
                'service_code' => $serviceCode !== '' ? $serviceCode : (string) $lockedClient->ussd_code,
                'session_data' => $rawText !== '' ? $rawText : ($errorMessage !== '' ? 'Gateway Event Error' : 'Session Event'),
                'cost' => $finalCharge,
                'client_price' => $finalCharge,
                'upstream_cost' => $atCost ?? 0,
                'duration_ms' => $durationMs,
                'hops' => $explicitHops ?? 1,
                'network_name' => $networkName !== 'Unknown' ? $networkName : null,
                'final_user_string' => $rawText,
                'status' => $resolvedStatus,
                'gateway_error' => $errorMessage !== '' ? $errorMessage : null,
                'last_app_response' => $lastAppResponse !== '' ? $lastAppResponse : null,
                'created_at' => now(),
                'logged_at' => now(),
                'reconciled_at' => now(),
            ]);

            $logId = $created->id;
        });

        $client->refresh();
        app(LowBalanceNotifier::class)->checkAndNotify($client);

        $outcome = $status !== '' ? $status : ($errorMessage !== '' ? 'Failed' : 'reconciled');
        if ($atCost === null) {
            $outcome = $errorMessage !== '' ? 'error:'.$errorMessage : 'reconciled:no_cost';
        }

        return [
            'ok' => true,
            'outcome' => $outcome,
            'reason' => $errorMessage !== '' ? $errorMessage : null,
            'ussd_log_id' => $logId,
        ];
    }

    public function resolveNetworkName(string $networkCode, string $phoneNumber = ''): string
    {
        if ($networkCode !== '') {
            $telco = UssdPricing::query()->where('network_code', $networkCode)->first();
            if ($telco) {
                return $telco->network_name;
            }
        }

        return match ($networkCode) {
            '62130' => 'MTN Nigeria',
            '62120' => 'Airtel Nigeria',
            '62150' => 'Glo Nigeria',
            '62160' => 'T2 (9mobile)',
            default => $this->inferNigerianCarrierFromPhone($phoneNumber),
        };
    }

    protected function inferNigerianCarrierFromPhone(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';
        if (str_starts_with($digits, '234')) {
            $digits = '0'.substr($digits, 3);
        }

        $p4 = substr($digits, 0, 4);
        if (in_array($p4, ['0803', '0806', '0703', '0706', '0810', '0813', '0814', '0816', '0903', '0906', '0913', '0916'], true)) {
            return 'MTN Nigeria';
        }
        if (in_array($p4, ['0802', '0808', '0708', '0701', '0812', '0901', '0902', '0904', '0907', '0912'], true)) {
            return 'Airtel Nigeria';
        }
        if (in_array($p4, ['0805', '0807', '0705', '0811', '0815', '0905', '0915'], true)) {
            return 'Glo Nigeria';
        }
        if (in_array($p4, ['0809', '0817', '0818', '0908', '0909'], true)) {
            return 'T2 (9mobile)';
        }

        return 'Unknown';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function billAndLogSession(
        Client $client,
        string $networkCode,
        string $phoneNumber,
        string $sessionId,
        string $serviceCode,
        string $cleanedText,
        string $rawText,
        array $payload = [],
        string $initialStatus = 'Success',
        ?string $gatewayError = null,
        ?string $lastAppResponse = null,
        bool $chargeWallet = true
    ): void {
        try {
            $multiplier = $client->getUssdCostMultiplier();
            $atCost = $this->parseAtCost($payload['cost'] ?? $payload['at_cost'] ?? null);
            $realCost = null;

            if ($atCost !== null) {
                $realCost = round($atCost * $multiplier, 2);
            }

            if ($realCost === null && $networkCode !== '') {
                $telco = UssdPricing::query()->where('network_code', $networkCode)->first();
                if ($telco) {
                    $realCost = (float) $telco->tokens_per_interval;
                }
            }

            if ($realCost === null) {
                $defaultFallback = round((float) config('services.ussd.default_cost', 3.50) * $multiplier, 2);
                $realCost = $client->getServicePrice('USSD', $defaultFallback);
            }

            if (! $chargeWallet) {
                $realCost = 0.0;
            }

            $networkName = $this->resolveNetworkName($networkCode, $phoneNumber);
            $hopsByPath = $rawText === '' ? 1 : (substr_count($rawText, '*') + 2);
            $sessionData = $cleanedText !== '' ? $cleanedText : 'Main Menu';

            DB::transaction(function () use (
                $client,
                $realCost,
                $atCost,
                $networkName,
                $hopsByPath,
                $phoneNumber,
                $sessionId,
                $serviceCode,
                $sessionData,
                $rawText,
                $initialStatus,
                $gatewayError,
                $lastAppResponse,
                $chargeWallet
            ): void {
                $lockedClient = Client::query()->whereKey($client->id)->lockForUpdate()->first();

                if ($chargeWallet && $lockedClient && $realCost > 0) {
                    $lockedClient->decrement('token_balance', $realCost);
                }

                if ($sessionId !== '') {
                    $existing = UssdLog::query()
                        ->where('client_id', $client->id)
                        ->where('session_id', $sessionId)
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();

                    if ($existing) {
                        $nextHops = max((int) ($existing->hops ?? 1) + 1, $hopsByPath);
                        $newCost = round((float) $existing->cost + $realCost, 2);
                        $newClientPrice = round((float) ($existing->client_price ?? $existing->cost) + $realCost, 2);

                        $existing->update([
                            'phone_number' => $phoneNumber !== '' ? $phoneNumber : $existing->phone_number,
                            'service_code' => $serviceCode !== '' ? $serviceCode : $existing->service_code,
                            'session_data' => $sessionData,
                            'cost' => $newCost,
                            'client_price' => $newClientPrice,
                            'upstream_cost' => $atCost ?? (float) ($existing->upstream_cost ?? 0),
                            'hops' => $nextHops,
                            'network_name' => $networkName !== 'Unknown' ? $networkName : $existing->network_name,
                            'final_user_string' => $rawText,
                            'status' => $existing->reconciled_at ? $existing->status : $initialStatus,
                            'gateway_error' => $gatewayError ?: $existing->gateway_error,
                            'last_app_response' => $lastAppResponse ?: $existing->last_app_response,
                            'logged_at' => now(),
                            'reconciled_at' => $atCost !== null ? now() : $existing->reconciled_at,
                        ]);

                        return;
                    }
                }

                UssdLog::create([
                    'client_id' => $client->id,
                    'phone_number' => $phoneNumber,
                    'session_id' => $sessionId,
                    'service_code' => $serviceCode,
                    'session_data' => $sessionData,
                    'cost' => $realCost,
                    'client_price' => $realCost,
                    'upstream_cost' => $atCost ?? 0,
                    'hops' => $hopsByPath,
                    'network_name' => $networkName !== 'Unknown' ? $networkName : null,
                    'final_user_string' => $rawText,
                    'status' => $initialStatus,
                    'gateway_error' => $gatewayError,
                    'last_app_response' => $lastAppResponse,
                    'created_at' => now(),
                    'logged_at' => now(),
                    'reconciled_at' => $atCost !== null ? now() : null,
                ]);
            });

            $client->refresh();
            app(LowBalanceNotifier::class)->checkAndNotify($client);
        } catch (\Throwable $e) {
            Log::error('USSD billing/logging error', [
                'sessionId' => $sessionId,
                'serviceCode' => $serviceCode,
                'error' => $e->getMessage(),
            ]);
            $this->lastGatewayError = $this->lastGatewayError ?: ('Logging error: '.$e->getMessage());
        }
    }

    /**
     * Traverse the database menu tree or forward API nodes.
     *
     * @param  array<int, string>  $inputs
     */
    protected function resolveMenuResponse(
        UssdMenu $menu,
        array $inputs,
        string $sessionId,
        string $serviceCode,
        string $phoneNumber,
        string $cleanedText
    ): string {
        $rootNode = UssdMenuItem::query()
            ->where('menu_id', $menu->id)
            ->where(fn ($q) => $q->whereNull('parent_item_id')->orWhere('parent_item_id', 0))
            ->orderByRaw("CASE WHEN UPPER(option_trigger) = 'ROOT' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if (! $rootNode) {
            return 'END System: Menu is empty. Please add items in the builder.';
        }

        $currentNode = $rootNode;
        $isApiRoute = false;

        foreach ($inputs as $index => $input) {
            $nextNode = UssdMenuItem::query()
                ->where('menu_id', $menu->id)
                ->where('parent_item_id', $currentNode->id)
                ->where('option_trigger', (string) $input)
                ->first();

            // Support flat menus where top-level options also have parent_item_id = NULL
            if (! $nextNode && $index === 0 && strtoupper($currentNode->option_trigger) !== 'ROOT') {
                $nextNode = UssdMenuItem::query()
                    ->where('menu_id', $menu->id)
                    ->whereNull('parent_item_id')
                    ->where('option_trigger', (string) $input)
                    ->first();
            }

            if (! $nextNode) {
                return 'END Invalid option. Please try again.';
            }

            $currentNode = $nextNode;

            if ($currentNode->response_type === 'API') {
                $isApiRoute = true;
                break;
            }
        }

        if ($isApiRoute) {
            return $this->forwardToExternalApi($sessionId, $serviceCode, $phoneNumber, $cleanedText);
        }

        $responseType = $currentNode->response_type === 'END' ? 'END' : 'CON';
        $responseBody = $currentNode->displayBody();

        if ($responseType === 'CON' && count($inputs) > 0 && ! str_contains($responseBody, '0. Back')) {
            $responseBody .= "\n0. Back";
        }

        return "{$responseType} {$responseBody}";
    }

    protected function forwardToExternalApi(
        string $sessionId,
        string $serviceCode,
        string $phoneNumber,
        string $cleanedText
    ): string {
        $url = config('services.ussd.modern_lottery_url');

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post($url, [
                    'sessionId' => $sessionId,
                    'serviceCode' => $serviceCode,
                    'phoneNumber' => $phoneNumber,
                    'text' => $cleanedText,
                ]);

            if ($response->successful()) {
                $body = trim((string) $response->body());
                if ($body === '' || (! str_starts_with($body, 'CON ') && ! str_starts_with($body, 'END '))) {
                    $this->lastGatewayError = 'External USSD API returned invalid CON/END response.';
                    Log::warning('External USSD API invalid body', ['status' => $response->status(), 'body' => mb_substr($body, 0, 200)]);

                    return 'END External service returned an invalid response. Try again later.';
                }

                return $body;
            }

            $this->lastGatewayError = 'External USSD API HTTP '.$response->status();
            Log::warning('External USSD API returned error status', ['status' => $response->status()]);

            return 'END External service is temporarily unavailable. Try again later.';
        } catch (\Throwable $e) {
            $this->lastGatewayError = 'External USSD API error: '.$e->getMessage();
            Log::error('External USSD API error', ['error' => $e->getMessage()]);

            return 'END External service is currently unreachable. Try again later.';
        }
    }
}
