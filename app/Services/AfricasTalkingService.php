<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AfricasTalkingService
{
    /**
     * Send an SMS via Africa's Talking (or simulate when dry_run/no key).
     *
     * @return array{ok: bool, status: string, message: string, raw?: mixed}
     */
    public function sendSms(string $to, string $message, ?string $senderId = null): array
    {
        $apiKey = config('services.africastalking.api_key');
        $username = config('services.africastalking.username');
        $dryRun = filter_var(config('services.africastalking.dry_run', true), FILTER_VALIDATE_BOOLEAN);

        if ($dryRun || empty($apiKey) || empty($username)) {
            return [
                'ok' => true,
                'status' => 'Simulated',
                'message' => 'SMS queued in sandbox/dry-run mode.',
            ];
        }

        $baseUrl = rtrim((string) config('services.africastalking.base_url'), '/');
        $payload = [
            'username' => $username,
            'to' => $to,
            'message' => $message,
        ];

        $from = $senderId ?: config('services.africastalking.sender_id');
        if (! empty($from)) {
            $payload['from'] = $from;
        }

        $response = Http::withHeaders([
            'apiKey' => $apiKey,
            'Accept' => 'application/json',
        ])->asForm()->timeout(15)->post("{$baseUrl}/version1/messaging", $payload);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'status' => 'Failed',
                'message' => 'Africa\'s Talking API error: HTTP '.$response->status(),
                'raw' => $response->json(),
            ];
        }

        $data = $response->json();
        $recipientStatus = $data['SMSMessageData']['Recipients'][0]['status'] ?? 'Sent';

        return [
            'ok' => true,
            'status' => (string) $recipientStatus,
            'message' => 'SMS dispatched successfully.',
            'raw' => $data,
        ];
    }

    /**
     * Send Airtime via Africa's Talking (or simulate when dry_run/no key).
     *
     * @return array{ok: bool, status: string, message: string, raw?: mixed}
     */
    public function sendAirtime(string $phoneNumber, float $amount, string $currencyCode = 'NGN'): array
    {
        $apiKey = config('services.africastalking.api_key');
        $username = config('services.africastalking.username');
        $dryRun = filter_var(config('services.africastalking.dry_run', true), FILTER_VALIDATE_BOOLEAN);

        if ($dryRun || empty($apiKey) || empty($username)) {
            return [
                'ok' => true,
                'status' => 'Simulated',
                'message' => 'Airtime request recorded in sandbox/dry-run mode.',
            ];
        }

        $baseUrl = rtrim((string) config('services.africastalking.base_url'), '/');
        $recipients = json_encode([[
            'phoneNumber' => $phoneNumber,
            'amount' => "{$currencyCode} {$amount}",
        ]]);

        $response = Http::withHeaders([
            'apiKey' => $apiKey,
            'Accept' => 'application/json',
        ])->asForm()->timeout(15)->post("{$baseUrl}/version1/airtime/send", [
            'username' => $username,
            'recipients' => $recipients,
        ]);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'status' => 'Failed',
                'message' => 'Africa\'s Talking Airtime error: HTTP '.$response->status(),
                'raw' => $response->json(),
            ];
        }

        $data = $response->json();
        $status = $data['responses'][0]['status'] ?? 'Sent';

        return [
            'ok' => true,
            'status' => (string) $status,
            'message' => 'Airtime dispatched successfully.',
            'raw' => $data,
        ];
    }
}
