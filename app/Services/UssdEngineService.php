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
        $sessionId = (string) ($payload['sessionId'] ?? '');
        $serviceCode = trim((string) ($payload['serviceCode'] ?? ''));
        $phoneNumber = (string) ($payload['phoneNumber'] ?? '');
        $rawText = (string) ($payload['text'] ?? '');
        $networkCode = trim((string) ($payload['networkCode'] ?? ''));

        $inputs = $this->parseInputs($rawText);
        $cleanedText = implode('*', $inputs);

        $client = Client::query()->where('ussd_code', $serviceCode)->first();
        if (! $client) {
            return 'END Error: USSD Code not recognized.';
        }

        if ($client->status === 'suspended') {
            return 'END Error: Client account is suspended.';
        }

        $menu = UssdMenu::query()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('is_archived', false)->orWhereNull('is_archived'))
            ->first();

        if (! $menu) {
            return 'END Error: No active menu found.';
        }

        if ($billClient) {
            $this->billAndLogSession($client, $networkCode, $phoneNumber, $sessionId, $serviceCode, $cleanedText, $rawText, $payload);
        }

        return $this->resolveMenuResponse($menu, $inputs, $sessionId, $serviceCode, $phoneNumber, $cleanedText);
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
     * Reconcile final session cost reported by Africa's Talking /events webhook using client tier USSD multiplier.
     *
     * @param  array<string, mixed>  $payload
     */
    public function reconcileEventCost(array $payload): void
    {
        $atCost = $this->parseAtCost($payload['cost'] ?? $payload['at_cost'] ?? null);
        if ($atCost === null) {
            return;
        }

        $sessionId = trim((string) ($payload['sessionId'] ?? ''));
        $serviceCode = trim((string) ($payload['serviceCode'] ?? ''));
        $phoneNumber = (string) ($payload['phoneNumber'] ?? '');
        $networkCode = trim((string) ($payload['networkCode'] ?? ''));
        $durationMs = isset($payload['durationInMillis']) && is_numeric($payload['durationInMillis'])
            ? max(0, (int) $payload['durationInMillis'])
            : null;
        $explicitHops = isset($payload['hopsCount']) && is_numeric($payload['hopsCount'])
            ? max(1, (int) $payload['hopsCount'])
            : null;
        $status = trim((string) ($payload['status'] ?? 'Success'));
        $rawText = (string) ($payload['text'] ?? ($payload['input'] ?? ''));
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
            ]);

            return;
        }

        $clientCharge = round($atCost * $client->getUssdCostMultiplier(), 2);

        DB::transaction(function () use ($client, $clientCharge, $atCost, $sessionId, $serviceCode, $phoneNumber, $status, $rawText, $durationMs, $explicitHops, $networkName): void {
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
                    $delta = round($clientCharge - $alreadyBilled, 2);

                    if ($delta > 0) {
                        $deduct = min((float) $lockedClient->token_balance, $delta);
                        if ($deduct > 0) {
                            $lockedClient->decrement('token_balance', $deduct);
                        }
                    } elseif ($delta < 0 && $alreadyBilled > 0) {
                        $lockedClient->increment('token_balance', abs($delta));
                    }

                    $lastLog = $sessionLogs->last();
                    if ($sessionLogs->count() > 1) {
                        UssdLog::query()
                            ->where('session_id', $sessionId)
                            ->where('client_id', $lockedClient->id)
                            ->where('id', '!=', $lastLog->id)
                            ->update([
                                'cost' => 0,
                                'client_price' => 0,
                                'upstream_cost' => 0,
                            ]);
                    }

                    $lastLog->update([
                        'cost' => $clientCharge,
                        'client_price' => $clientCharge,
                        'upstream_cost' => $atCost,
                        'duration_ms' => $durationMs ?? $lastLog->duration_ms,
                        'hops' => $explicitHops ?? max((int) $sessionLogs->count(), (int) ($lastLog->hops ?? 1)),
                        'network_name' => $networkName !== 'Unknown' ? $networkName : $lastLog->network_name,
                        'status' => $status !== '' ? $status : ($lastLog->status ?: 'Success'),
                    ]);

                    return;
                }
            }

            if ((float) $lockedClient->token_balance >= $clientCharge && $clientCharge > 0) {
                $lockedClient->decrement('token_balance', $clientCharge);
            }

            UssdLog::create([
                'client_id' => $lockedClient->id,
                'phone_number' => $phoneNumber,
                'session_id' => $sessionId,
                'service_code' => $serviceCode !== '' ? $serviceCode : (string) $lockedClient->ussd_code,
                'session_data' => $rawText !== '' ? $rawText : 'Session Event',
                'cost' => $clientCharge,
                'client_price' => $clientCharge,
                'upstream_cost' => $atCost,
                'duration_ms' => $durationMs,
                'hops' => $explicitHops ?? 1,
                'network_name' => $networkName !== 'Unknown' ? $networkName : null,
                'final_user_string' => $rawText,
                'status' => $status !== '' ? $status : 'Success',
                'created_at' => now(),
                'logged_at' => now(),
            ]);
        });

        $client->refresh();
        app(LowBalanceNotifier::class)->checkAndNotify($client);
    }

    protected function resolveNetworkName(string $networkCode, string $phoneNumber = ''): string
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
        array $payload = []
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

            $networkName = $this->resolveNetworkName($networkCode, $phoneNumber);
            $hops = $cleanedText === '' ? 1 : (substr_count($cleanedText, '*') + 1);

            DB::transaction(function () use ($client, $realCost, $atCost, $networkName, $hops, $phoneNumber, $sessionId, $serviceCode, $cleanedText, $rawText): void {
                $lockedClient = Client::query()->whereKey($client->id)->lockForUpdate()->first();

                if ($lockedClient && (float) $lockedClient->token_balance >= $realCost) {
                    $lockedClient->decrement('token_balance', $realCost);
                }

                UssdLog::create([
                    'client_id' => $client->id,
                    'phone_number' => $phoneNumber,
                    'session_id' => $sessionId,
                    'service_code' => $serviceCode,
                    'session_data' => $cleanedText !== '' ? $cleanedText : 'Main Menu',
                    'cost' => $realCost,
                    'client_price' => $realCost,
                    'upstream_cost' => $atCost ?? 0,
                    'hops' => $hops,
                    'network_name' => $networkName !== 'Unknown' ? $networkName : null,
                    'final_user_string' => $rawText,
                    'status' => 'Success',
                    'created_at' => now(),
                    'logged_at' => now(),
                ]);
            });

            $client->refresh();
            app(LowBalanceNotifier::class)->checkAndNotify($client);
        } catch (\Throwable $e) {
            Log::error('USSD billing/logging error', ['error' => $e->getMessage()]);
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
                ->timeout(10)
                ->post($url, [
                    'sessionId' => $sessionId,
                    'serviceCode' => $serviceCode,
                    'phoneNumber' => $phoneNumber,
                    'text' => $cleanedText,
                ]);

            if ($response->successful()) {
                return (string) $response->body();
            }

            Log::warning('External USSD API returned error status', ['status' => $response->status()]);

            return 'END External service is temporarily unavailable. Try again later.';
        } catch (\Throwable $e) {
            Log::error('External USSD API error', ['error' => $e->getMessage()]);

            return 'END External service is currently unreachable. Try again later.';
        }
    }
}
