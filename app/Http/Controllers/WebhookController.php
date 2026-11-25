<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Transaction;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function paystack(Request $request, PaystackService $paystack): Response
    {
        $rawBody = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        if (! $paystack->verifyWebhookSignature($rawBody, $signature)) {
            return response('Invalid Signature', 401);
        }

        $event = $request->json()->all();

        if (($event['event'] ?? null) === 'charge.success') {
            $data = $event['data'] ?? [];
            $reference = (string) ($data['reference'] ?? '');
            $amountKobo = (int) ($data['amount'] ?? 0);
            $clientId = $data['metadata']['client_id'] ?? null;
            $tokenAmount = (int) round($amountKobo / 100);

            if ($reference !== '' && $clientId && $tokenAmount > 0) {
                DB::transaction(function () use ($reference, $clientId, $tokenAmount): void {
                    $tx = Transaction::query()->where('reference', $reference)->lockForUpdate()->first();

                    if ($tx && strtolower((string) $tx->status) === 'success') {
                        return;
                    }

                    if ($tx) {
                        $tx->update([
                            'status' => 'success',
                            'tokens_purchased' => $tokenAmount,
                        ]);
                    } else {
                        Transaction::create([
                            'client_id' => $clientId,
                            'type' => 'credit',
                            'reference' => $reference,
                            'amount' => $tokenAmount,
                            'tokens_purchased' => $tokenAmount,
                            'status' => 'success',
                            'gateway' => 'paystack',
                            'created_at' => now(),
                        ]);
                    }

                    Client::query()->whereKey($clientId)->increment('token_balance', $tokenAmount);
                });

                Log::info('Paystack webhook credited client', [
                    'client_id' => $clientId,
                    'reference' => $reference,
                    'tokens' => $tokenAmount,
                ]);
            }
        }

        return response('OK', 200);
    }
}
