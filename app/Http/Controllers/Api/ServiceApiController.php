<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AirtimeLog;
use App\Models\ApiKey;
use App\Models\Client;
use App\Models\SmsLog;
use App\Services\AfricasTalkingService;
use App\Services\LowBalanceNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceApiController extends Controller
{
    public function sendSms(Request $request, AfricasTalkingService $at, LowBalanceNotifier $notifier): JsonResponse
    {
        $client = $this->authenticateApiClient($request);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        $data = $request->validate([
            'to' => ['required'],
            'message' => ['required', 'string', 'max:1600'],
            'sender_id' => ['nullable', 'string', 'max:20'],
        ]);

        $recipients = $this->normalizeRecipients($data['to']);
        if (empty($recipients)) {
            return response()->json(['ok' => false, 'message' => 'No valid recipient numbers supplied.'], 422);
        }

        $rate = $client->getServicePrice('SMS', 3.50);
        $pages = max(1, (int) ceil(mb_strlen($data['message']) / 160));
        $unitCost = round($pages * $rate, 2);
        $totalCost = round($unitCost * count($recipients), 2);
        $senderId = trim((string) ($data['sender_id'] ?? $client->sender_id ?? ''));

        $charged = DB::transaction(function () use ($client, $totalCost): bool {
            $locked = Client::query()->whereKey($client->id)->lockForUpdate()->first();
            if (! $locked || (float) $locked->token_balance < $totalCost) {
                return false;
            }
            $locked->decrement('token_balance', $totalCost);

            return true;
        });

        if (! $charged) {
            return response()->json(['ok' => false, 'message' => "Insufficient wallet balance. Required: ₦{$totalCost}."], 402);
        }

        $succeeded = 0;
        $failed = 0;
        foreach ($recipients as $phone) {
            $result = $at->sendSms($phone, $data['message'], $senderId ?: null);
            if (! $result['ok']) {
                $failed++;
                Client::query()->whereKey($client->id)->increment('token_balance', $unitCost);
                SmsLog::create([
                    'client_id' => $client->id,
                    'sender_id' => $senderId ?: 'System',
                    'phone_number' => $phone,
                    'message' => $data['message'],
                    'cost' => 0,
                    'status' => 'Failed',
                    'created_at' => now(),
                ]);

                continue;
            }

            $succeeded++;
            SmsLog::create([
                'client_id' => $client->id,
                'sender_id' => $senderId ?: 'System',
                'phone_number' => $phone,
                'message' => $data['message'],
                'cost' => $unitCost,
                'status' => $result['status'],
                'created_at' => now(),
            ]);
        }

        $client->refresh();
        $notifier->checkAndNotify($client);

        return response()->json([
            'ok' => $succeeded > 0,
            'sent' => $succeeded,
            'failed' => $failed,
            'total_cost' => round($succeeded * $unitCost, 2),
            'balance' => (float) $client->token_balance,
        ], $succeeded > 0 ? 200 : 502);
    }

    public function sendAirtime(Request $request, AfricasTalkingService $at, LowBalanceNotifier $notifier): JsonResponse
    {
        $client = $this->authenticateApiClient($request);
        if ($client instanceof JsonResponse) {
            return $client;
        }

        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:50', 'max:50000'],
            'network' => ['nullable', 'string', 'max:50'],
        ]);

        $amount = round((float) $data['amount'], 2);

        $charged = DB::transaction(function () use ($client, $amount): bool {
            $locked = Client::query()->whereKey($client->id)->lockForUpdate()->first();
            if (! $locked || (float) $locked->token_balance < $amount) {
                return false;
            }
            $locked->decrement('token_balance', $amount);

            return true;
        });

        if (! $charged) {
            return response()->json(['ok' => false, 'message' => "Insufficient wallet balance. Required: ₦{$amount}."], 402);
        }

        $result = $at->sendAirtime($data['phone_number'], $amount);
        if (! $result['ok']) {
            Client::query()->whereKey($client->id)->increment('token_balance', $amount);

            return response()->json(['ok' => false, 'message' => $result['message']], 502);
        }

        AirtimeLog::create([
            'client_id' => $client->id,
            'phone_number' => $data['phone_number'],
            'network' => $data['network'] ?? 'Nigeria',
            'amount' => $amount,
            'status' => $result['status'],
            'created_at' => now(),
        ]);

        $client->refresh();
        $notifier->checkAndNotify($client);

        return response()->json([
            'ok' => true,
            'status' => $result['status'],
            'amount' => $amount,
            'balance' => (float) $client->token_balance,
        ]);
    }

    protected function authenticateApiClient(Request $request): Client|JsonResponse
    {
        $rawKey = trim((string) ($request->header('X-API-Key') ?: $request->bearerToken() ?: $request->input('api_key', '')));
        if ($rawKey === '') {
            return response()->json(['ok' => false, 'message' => 'Missing X-API-Key header.'], 401);
        }

        $keyRecord = ApiKey::query()->with('client')->where('api_key', $rawKey)->first();
        if (! $keyRecord || ! $keyRecord->client || $keyRecord->client->status !== 'active') {
            return response()->json(['ok' => false, 'message' => 'Invalid or inactive API key.'], 401);
        }

        $whitelist = trim((string) ($keyRecord->whitelisted_ips ?? ''));
        if ($whitelist !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $whitelist)));
            if (! in_array($request->ip(), $allowed, true)) {
                return response()->json(['ok' => false, 'message' => 'Client IP is not whitelisted for this API key.'], 403);
            }
        }

        return $keyRecord->client;
    }

    /**
     * @param  mixed  $rawTo
     * @return array<int, string>
     */
    protected function normalizeRecipients(mixed $rawTo): array
    {
        $items = is_array($rawTo) ? $rawTo : preg_split('/[\s,;]+/', (string) $rawTo, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $items ?: []))));
    }
}
