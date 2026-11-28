<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PerfexCrmService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.perfex.url')) && ! empty(config('services.perfex.api_token'));
    }

    /**
     * Verify connectivity and API token against Perfex CRM.
     *
     * @return array{ok: bool, customers_count: int, invoices_count: int, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'customers_count' => 0,
                'invoices_count' => 0,
                'message' => 'Set PERFEX_CRM_URL and PERFEX_API_TOKEN in .env first.',
            ];
        }

        $customers = $this->fetchCustomers();
        $invoices = $this->fetchInvoices();

        if (! $customers['ok'] && ! $invoices['ok']) {
            return [
                'ok' => false,
                'customers_count' => 0,
                'invoices_count' => 0,
                'message' => $invoices['message'] ?: $customers['message'],
            ];
        }

        return [
            'ok' => true,
            'customers_count' => count($customers['customers']),
            'invoices_count' => count($invoices['invoices']),
            'message' => 'Connected to '.rtrim((string) config('services.perfex.url'), '/').'.',
        ];
    }

    /**
     * Fetch customers list from Perfex CRM.
     *
     * @return array{ok: bool, customers: array<int, array<string, mixed>>, message: string}
     */
    public function fetchCustomers(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'customers' => [], 'message' => 'Perfex CRM API token is not configured in .env.'];
        }

        try {
            $res = $this->requestPerfex('GET', 'customers');

            if (! $res->successful()) {
                return [
                    'ok' => false,
                    'customers' => [],
                    'message' => $this->formatApiError($res->status(), $res->json() ?: [], $res->body(), 'customers'),
                ];
            }

            $rows = $this->extractListPayload($res->json());

            return [
                'ok' => true,
                'customers' => $rows,
                'message' => count($rows).' customer(s) loaded.',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'customers' => [], 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch invoices from Perfex CRM (optionally filtered to a Perfex customer ID).
     *
     * @return array{ok: bool, invoices: array<int, array<string, mixed>>, message: string}
     */
    public function fetchInvoices(?int $perfexClientId = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'invoices' => [], 'message' => 'Perfex CRM API token is not configured in .env.'];
        }

        try {
            $query = ($perfexClientId !== null && $perfexClientId > 0) ? ['clientid' => $perfexClientId] : [];
            $res = $this->requestPerfex('GET', 'invoices', $query);

            if (! $res->successful()) {
                return [
                    'ok' => false,
                    'invoices' => [],
                    'message' => $this->formatApiError($res->status(), $res->json() ?: [], $res->body(), 'invoices'),
                ];
            }

            $rows = $this->extractListPayload($res->json());
            if ($perfexClientId !== null && $perfexClientId > 0) {
                $rows = array_values(array_filter(
                    $rows,
                    fn ($inv) => (int) ($inv['clientid'] ?? 0) === $perfexClientId
                ));
            }

            $normalized = array_map(function (array $inv): array {
                $prefix = (string) ($inv['prefix'] ?? 'INV-');
                $num = (string) ($inv['number'] ?? $inv['id'] ?? '');
                $formattedNum = (string) ($inv['formatted_number'] ?? ($prefix.$num));
                $statusCode = (string) ($inv['status'] ?? '');
                $statusLabel = match ($statusCode) {
                    '1' => 'Unpaid',
                    '2' => 'Paid',
                    '3' => 'Partially Paid',
                    '4' => 'Overdue',
                    '5' => 'Cancelled',
                    '6' => 'Draft',
                    default => $statusCode !== '' ? ucfirst($statusCode) : 'Unknown',
                };

                return [
                    'id' => $inv['id'] ?? null,
                    'number' => $formattedNum,
                    'clientid' => $inv['clientid'] ?? null,
                    'company' => $inv['company'] ?? $inv['client_name'] ?? ('Client #'.($inv['clientid'] ?? '—')),
                    'date' => $inv['date'] ?? '—',
                    'duedate' => $inv['duedate'] ?? '—',
                    'total' => (float) ($inv['total'] ?? 0),
                    'status' => $statusLabel,
                ];
            }, $rows);

            return [
                'ok' => true,
                'invoices' => $normalized,
                'message' => count($normalized).' invoice(s) loaded from Perfex CRM.',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'invoices' => [], 'message' => $e->getMessage()];
        }
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

        try {
            $searchPaths = array_filter([
                $client->email ? 'contacts/search/'.rawurlencode($client->email) : null,
                $client->name ? 'customers/search/'.rawurlencode($client->name) : null,
                $client->email ? 'customers/search/'.rawurlencode($client->email) : null,
            ]);

            foreach ($searchPaths as $path) {
                $search = $this->requestPerfex('GET', $path);
                if ($search->successful()) {
                    $rows = $this->extractListPayload($search->json());
                    $customerId = (int) ($rows[0]['userid'] ?? $rows[0]['id'] ?? 0);
                    if ($customerId > 0) {
                        $client->update(['perfex_client_id' => $customerId]);

                        return ['ok' => true, 'customer_id' => $customerId, 'message' => 'Matched existing Perfex customer.'];
                    }
                }
            }

            $customerPayload = [
                'company' => $client->name,
                'phonenumber' => '',
                'country' => 160,
                'default_currency' => (int) config('services.perfex.currency_id', 1),
                'active' => $client->status === 'active' ? 1 : 0,
            ];

            $created = $this->requestPerfex('POST', 'customers', $customerPayload);
            $payload = $created->json() ?: [];
            $customerId = (int) ($payload['data']['userid'] ?? $payload['userid'] ?? $payload['id'] ?? 0);

            if ($created->successful() && $customerId > 0) {
                $client->update(['perfex_client_id' => $customerId]);

                return ['ok' => true, 'customer_id' => $customerId, 'message' => 'Created Perfex CRM customer.'];
            }

            return [
                'ok' => false,
                'customer_id' => null,
                'message' => $this->formatApiError($created->status(), $payload, $created->body(), 'create customer'),
            ];
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

        $rate = round($amount, 2);
        $invoiceNumber = $this->nextInvoiceNumber();
        $paymentMode = (string) config('services.perfex.payment_mode', '1');

        $invoicePayload = [
            'clientid' => $customerResult['customer_id'],
            'number' => $invoiceNumber,
            'date' => now()->format('Y-m-d'),
            'duedate' => now()->addDays(7)->format('Y-m-d'),
            'currency' => (int) config('services.perfex.currency_id', 1),
            'subtotal' => $rate,
            'total' => $rate,
            'billing_street' => 'Nigeria',
            'allowed_payment_modes' => [$paymentMode],
            'newitems' => [
                [
                    'description' => $description,
                    'long_description' => $longDescription ?: ('Q4I USSD & Comms Portal • Account: '.$client->email),
                    'qty' => 1,
                    'rate' => $rate,
                    'unit' => 'cycle',
                ],
            ],
            'clientnote' => 'Thank you for choosing Q4 International Ltd (ussd.q4iltd.com).',
        ];

        try {
            $response = $this->requestPerfex('POST', 'invoices', $invoicePayload);
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
                'message' => $this->formatApiError($response->status(), $payload, $response->body(), 'create invoice'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'invoice_id' => null, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send an HTTP request to Perfex CRM, supporting both configured prefix (e.g. q4i_api/api)
     * and automatic 404 fallback from /api/* to /q4i_api/api/*.
     *
     * @param  array<string, mixed>  $data
     */
    protected function requestPerfex(string $method, string $relativePath, array $data = []): Response
    {
        $baseUrl = rtrim((string) config('services.perfex.url'), '/');
        $token = (string) config('services.perfex.api_token');
        $configuredPrefix = trim((string) config('services.perfex.api_prefix', ''), '/');

        $prefixes = $configuredPrefix !== ''
            ? [$configuredPrefix]
            : ['api', 'q4i_api/api'];

        $lastResponse = null;

        foreach ($prefixes as $prefix) {
            $path = $this->translatePathForPrefix($relativePath, $prefix);
            $url = "{$baseUrl}/{$prefix}/{$path}";

            $lastResponse = $this->sendSingleRequest($method, $url, $token, $data);
            if ($lastResponse->status() !== 404) {
                return $lastResponse;
            }
        }

        return $lastResponse;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function sendSingleRequest(string $method, string $url, string $token, array $data): Response
    {
        $pending = Http::withHeaders(['Authtoken' => $token])
            ->acceptJson()
            ->timeout(12);

        if (strtoupper($method) === 'GET') {
            return $pending->get($url, $data);
        }

        $res = $pending->asForm()->post($url, $data);
        if (! $res->successful() && in_array($res->status(), [400, 415], true)) {
            $res = Http::withHeaders(['Authtoken' => $token])
                ->acceptJson()
                ->timeout(12)
                ->post($url, $data);
        }

        return $res;
    }

    protected function translatePathForPrefix(string $relativePath, string $prefix): string
    {
        $clean = ltrim($relativePath, '/');
        if (str_contains($prefix, 'q4i_api')) {
            $clean = str_replace(['customers/search/', 'contacts/search/'], ['customers_search/', 'contacts_search/'], $clean);
        }

        return $clean;
    }

    protected function nextInvoiceNumber(): int
    {
        try {
            $res = $this->requestPerfex('GET', 'invoices');
            if ($res->successful()) {
                $rows = $this->extractListPayload($res->json());
                $maxNum = 0;
                foreach ($rows as $row) {
                    $n = (int) ($row['number'] ?? 0);
                    if ($n > $maxNum) {
                        $maxNum = $n;
                    }
                }
                if ($maxNum > 0) {
                    return $maxNum + 1;
                }
            }
        } catch (\Throwable) {
        }

        return (int) substr((string) now()->timestamp, -6);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractListPayload(mixed $json): array
    {
        if (! is_array($json)) {
            return [];
        }

        if (isset($json['data']) && is_array($json['data'])) {
            return array_values(array_filter($json['data'], 'is_array'));
        }

        if (array_is_list($json)) {
            return array_values(array_filter($json, 'is_array'));
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $json
     */
    protected function formatApiError(int $status, array $json, string $rawBody, string $action): string
    {
        $msg = $json['message'] ?? $json['error'] ?? null;
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        if (empty($msg) && $rawBody !== '') {
            $msg = mb_substr(strip_tags($rawBody), 0, 180);
        }

        return "Perfex CRM {$action} failed (HTTP {$status})".($msg ? ": {$msg}" : '.');
    }
}
