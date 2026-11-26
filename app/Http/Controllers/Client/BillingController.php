<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Transaction;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, PaystackService $paystack): View
    {
        $client = $request->user();

        $transactions = Transaction::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate(15);

        $paystackReady = $paystack->isConfigured();

        return view('client.billing', compact('client', 'transactions', 'paystackReady'));
    }

    public function receipt(Request $request, Transaction $transaction): View
    {
        $user = $request->user();
        if (! $user->isAdmin() && (int) $transaction->client_id !== (int) $user->id) {
            abort(403, 'You do not have permission to view this receipt.');
        }

        $transaction->load('client');

        return view('client.receipt', [
            'client' => $transaction->client ?? $user,
            'transaction' => $transaction,
        ]);
    }

    public function initializePaystack(Request $request, PaystackService $paystack): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100', 'max:10000000'],
        ]);

        $client = $request->user();
        $amount = round((float) $data['amount'], 2);
        $reference = 'REF-'.now()->timestamp.'-'.$client->id.'-'.random_int(100, 999);

        Transaction::create([
            'client_id' => $client->id,
            'type' => 'credit',
            'reference' => $reference,
            'amount' => $amount,
            'tokens_purchased' => (int) round($amount),
            'status' => 'Pending',
            'gateway' => 'paystack',
            'created_at' => now(),
        ]);

        $init = $paystack->initialize(
            $client->email,
            $amount,
            $reference,
            route('billing.verify'),
            $client->id
        );

        if (! $init['ok'] || empty($init['authorization_url'])) {
            Transaction::query()->where('reference', $reference)->update(['status' => 'failed']);

            return back()->withErrors(['amount' => $init['message'] ?? 'Could not initialize Paystack checkout.']);
        }

        return redirect()->away($init['authorization_url']);
    }

    public function verifyPaystack(Request $request, PaystackService $paystack): RedirectResponse
    {
        $reference = (string) $request->query('reference', '');
        if ($reference === '') {
            return redirect()->route('billing.index')->withErrors(['billing' => 'Missing payment reference.']);
        }

        $tx = Transaction::query()
            ->where('reference', $reference)
            ->where('client_id', $request->user()->id)
            ->first();

        if (! $tx) {
            return redirect()->route('billing.index')->withErrors(['billing' => 'Transaction not found.']);
        }

        if (strtolower((string) $tx->status) === 'success') {
            return redirect()->route('billing.index')->with('status', 'Payment already verified and credited.');
        }

        $result = $paystack->verify($reference);
        if (! $result['ok']) {
            return redirect()->route('billing.index')->withErrors(['billing' => $result['message'] ?? 'Payment verification failed.']);
        }

        $creditAmount = (int) round((float) ($result['amount'] ?? $tx->amount));

        DB::transaction(function () use ($tx, $creditAmount): void {
            $lockedTx = Transaction::query()->whereKey($tx->id)->lockForUpdate()->first();
            if (! $lockedTx || strtolower((string) $lockedTx->status) === 'success') {
                return;
            }

            $lockedTx->update([
                'status' => 'success',
                'tokens_purchased' => $creditAmount,
            ]);

            Client::query()->whereKey($lockedTx->client_id)->increment('token_balance', $creditAmount);
        });

        return redirect()->route('billing.index')->with('status', "Wallet credited with ₦{$creditAmount} successfully.");
    }
}
