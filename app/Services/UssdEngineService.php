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
            $this->billAndLogSession($client, $networkCode, $phoneNumber, $sessionId, $serviceCode, $cleanedText, $rawText);
        }

        return $this->resolveMenuResponse($menu, $inputs, $sessionId, $serviceCode, $phoneNumber, $cleanedText);
    }

    protected function billAndLogSession(
        Client $client,
        string $networkCode,
        string $phoneNumber,
        string $sessionId,
        string $serviceCode,
        string $cleanedText,
        string $rawText
    ): void {
        try {
            $realCost = null;

            if ($networkCode !== '') {
                $telco = UssdPricing::query()->where('network_code', $networkCode)->first();
                if ($telco) {
                    $realCost = (float) $telco->tokens_per_interval;
                }
            }

            if ($realCost === null) {
                $realCost = $client->getServicePrice(
                    'USSD',
                    (float) config('services.ussd.default_cost', 3.50)
                );
            }

            DB::transaction(function () use ($client, $realCost, $phoneNumber, $sessionId, $serviceCode, $cleanedText, $rawText): void {
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
                    'final_user_string' => $rawText,
                    'status' => 'Success',
                    'created_at' => now(),
                    'logged_at' => now(),
                ]);
            });
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
