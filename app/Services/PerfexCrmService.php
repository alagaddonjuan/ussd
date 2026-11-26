<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;

class PerfexCrmService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.perfex.url')) && ! empty(config('services.perfex.api_token'));
    }

    /**
     * Resolve or create a customer record inside Perfex CRM (crm.q4iltd.com).
     *
     * @return array{ok: bool, customer_id: int|null, message: string}
     */
    public function syncCustomer(Client $client): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'customer_id' => null, 'message' => 'Perfex CRM REST API token is not configured in .env.'];
        }

        if ($client->perfex_client_id) {
            return ['ok' => true, 'customer_id' => (int) $client->perfex_client_id, 'message' => 'Mapped existing Perfex customer.'];
        }

        $baseUrl = rtrim((string) config('services.perfex.url'), '/');
        $token = (string) config('services.perfex.api_token');

        try {
            $search = Http::withHeaders(['Authtoken' => $token])
                ->acceptJson()
                ->timeout(10)
                ->get("{$baseUrl}/api/customers/search/".rawurlencode($client->email));

            if ($search->successful()) {
                $rows = $search->json();
                if (is_array($rows) && ! empty($rows[0]['userid'])) {
                    $customerId = (int) $rows[0]['userid'];
                    $client->update(['perfex_client_id' => $customerId]);

                    return ['ok' => true, 'customer_id' => $customerId, 'message' => 'Matched existing Perfex customer by email.'];
                }
            }

            $created = Http::withHeaders(['Authtoken' => $token])
                ->acceptJson()
                ->timeout(10)
                ->post("{$baseUrl}/api/customers", [
                    'company' => $client->name,
                    'phonenumber' => '',
                    'country' => 160,
                    'default_currency' => (int) config('services.perfex.currency_id', 1),
                    'active' => $client->status === 'active' ? 1 : 0,
                ]);

            $payload = $created->json() ?: [];
            $customerId = (int) ($payload['data']['userid'] ?? $payload['userid'] ?? $payload['id'] ?? 0);

            if ($created->successful() && $customerId > 0) {
                $client->update(['perfex_client_id' => $customerId]);

                return ['ok' => true, 'customer_id' => $customerId, 'message' => 'Created Perfex CRM customer.'];
            }

            return ['ok' => false, 'customer_id' => null, 'message' => $payload['message'] ?? 'Could not create customer in Perfex CRM.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'customer_id' => null, 'message' => $e->getMessage()];
        }
    }

    /**
     * Push an invoice line item (e.g., Monthly Maintenance or USSD top-up) to Perfex CRM.
     *
     * @return array{ok: bool, skipped?: bool, invoice_id: string|null, message: string}
     */
    public function pushInvoice(
        Client $client,
        float $amount,
        string $description,
        ?Transaction $transaction = null,
        ?string $longDescription = null
    ): array {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'skipped' => true,
                'invoice_id' => null,
                'message' => 'Perfex CRM API token not set yet (PERFEX_API_TOKEN).',
            ];
        }

        $customerResult = $this->syncCustomer($client);
        if (! $customerResult['ok'] || ! $customerResult['customer_id']) {
            return [
                'ok' => false,
                'invoice_id' => null,
                'message' => $customerResult['message'],
            ];
        }

        $baseUrl = rtrim((string) config('services.perfex.url'), '/');
        $token = (string) config('services.perfex.api_token');

        try {
            $response = Http::withHeaders(['Authtoken' => $token])
                ->acceptJson()
                ->timeout(12)
                ->post("{$baseUrl}/api/invoices", [
                    'clientid' => $customerResult['customer_id'],
                    'date' => now()->format('Y-m-d'),
                    'duedate' => now()->addDays(7)->format('Y-m-d'),
                    'currency' => (int) config('services.perfex.currency_id', 1),
                    'newitems' => [
                        [
                            'description' => $description,
                            'long_description' => $longDescription ?: ('Q4I USSD & Comms Portal • Account: '.$client->email),
                            'qty' => 1,
                            'rate' => round($amount, 2),
                            'unit' => 'cycle',
                        ],
                    ],
                    'clientnote' => 'Thank you for choosing Q4 International Ltd (ussd.q4iltd.com).',
                ]);

            $payload = $response->json() ?: [];
            $invoiceId = (string) ($payload['data']['id'] ?? $payload['invoice_id'] ?? $payload['id'] ?? '');

            if ($response->successful()) {
                if ($transaction && $invoiceId !== '') {
                    $transaction->update(['perfex_invoice_id' => $invoiceId]);
                }

                return [
                    'ok' => true,
                    'invoice_id' => $invoiceId !== '' ? $invoiceId : 'synced',
                    'message' => 'Invoice synced to Perfex CRM'.($invoiceId !== '' ? " (#{$invoiceId})" : '').'.',
                ];
            }

            return [
                'ok' => false,
                'invoice_id' => null,
                'message' => $payload['message'] ?? 'Perfex CRM returned HTTP '.$response->status(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'invoice_id' => null, 'message' => $e->getMessage()];
        }
    }
}
