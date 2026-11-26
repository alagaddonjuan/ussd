<?php

namespace App\Services;

use App\Mail\LowBalanceAlertMail;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LowBalanceNotifier
{
    public function checkAndNotify(Client $client): void
    {
        $threshold = (float) ($client->low_balance_threshold ?? 0);
        if ($threshold <= 0 || $client->isAdmin() || $client->status !== 'active') {
            return;
        }

        if ((float) $client->token_balance > $threshold) {
            return;
        }

        if ($client->low_balance_notified_at && $client->low_balance_notified_at->gt(now()->subHours(24))) {
            return;
        }

        try {
            Mail::to($client->email)->send(new LowBalanceAlertMail($client, $threshold));
            $client->update(['low_balance_notified_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Failed sending low balance alert', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
