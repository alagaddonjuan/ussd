<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaystackService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.paystack.secret_key'));
    }

    /**
     * @return array{ok: bool, authorization_url?: string, message?: string}
     */
    public function initialize(string $email, float $amountNgn, string $reference, string $callbackUrl, int $clientId): array
    {
        $secret = config('services.paystack.secret_key');
        if (empty($secret)) {
            return ['ok' => false, 'message' => 'Paystack secret key is not configured in .env.'];
        }

        $baseUrl = rtrim((string) config('services.paystack.base_url'), '/');

        $response = Http::withToken($secret)
            ->timeout(15)
            ->post("{$baseUrl}/transaction/initialize", [
                'email' => $email,
                'amount' => (int) round($amountNgn * 100),
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => ['client_id' => $clientId],
            ]);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            return [
                'ok' => false,
                'message' => (string) ($response->json('message') ?? 'Paystack initialization failed.'),
            ];
        }

        return [
            'ok' => true,
            'authorization_url' => (string) $response->json('data.authorization_url'),
        ];
    }

    /**
     * @return array{ok: bool, status?: string, amount?: float, message?: string}
     */
    public function verify(string $reference): array
    {
        $secret = config('services.paystack.secret_key');
        if (empty($secret)) {
            return ['ok' => false, 'message' => 'Paystack secret key is not configured.'];
        }

        $baseUrl = rtrim((string) config('services.paystack.base_url'), '/');

        $response = Http::withToken($secret)
            ->timeout(15)
            ->get("{$baseUrl}/transaction/verify/{$reference}");

        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'Failed to verify Paystack transaction.'];
        }

        $status = (string) $response->json('data.status');
        $amountKobo = (int) $response->json('data.amount');

        return [
            'ok' => $status === 'success',
            'status' => $status,
            'amount' => $amountKobo / 100,
            'message' => $status === 'success' ? 'Payment verified.' : 'Payment not completed.',
        ];
    }

    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '' || ! $signature) {
            return false;
        }

        $computed = hash_hmac('sha512', $rawBody, $secret);

        return hash_equals($computed, $signature);
    }
}
