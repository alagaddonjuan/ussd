<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AirtimeLog;
use App\Models\Client;
use App\Models\SmsLog;
use App\Models\UssdLog;
use App\Services\AfricasTalkingService;
use App\Services\LowBalanceNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user();
        $smsRate = $client->getServicePrice('SMS', 3.50);
        $airtimeRate = $client->getServicePrice('AIRTIME', 1.00);

        $smsLogs = SmsLog::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $airtimeLogs = AirtimeLog::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('client.services', compact(
            'client',
            'smsRate',
            'airtimeRate',
            'smsLogs',
            'airtimeLogs'
        ));
    }

    public function sendSms(Request $request, AfricasTalkingService $at, LowBalanceNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'max:32'],
            'message' => ['required', 'string', 'max:1600'],
        ]);

        $client = $request->user();
        $rate = $client->getServicePrice('SMS', 3.50);
        $pages = max(1, (int) ceil(mb_strlen($data['message']) / 160));
        $cost = round($pages * $rate, 2);

        $charged = DB::transaction(function () use ($client, $cost): bool {
            $locked = Client::query()->whereKey($client->id)->lockForUpdate()->first();
            if (! $locked || (float) $locked->token_balance < $cost) {
                return false;
            }

            $locked->decrement('token_balance', $cost);

            return true;
        });

        if (! $charged) {
            return back()
                ->withInput()
                ->withErrors(['sms' => "Insufficient token balance. Required: ₦{$cost}."]);
        }

        $result = $at->sendSms($data['to'], $data['message'], $client->sender_id);

        if (! $result['ok']) {
            Client::query()->whereKey($client->id)->increment('token_balance', $cost);

            SmsLog::create([
                'client_id' => $client->id,
                'sender_id' => $client->sender_id ?: 'System',
                'phone_number' => $data['to'],
                'message' => $data['message'],
                'cost' => 0,
                'status' => 'Failed',
                'created_at' => now(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['sms' => $result['message']]);
        }

        SmsLog::create([
            'client_id' => $client->id,
            'sender_id' => $client->sender_id ?: 'System',
            'phone_number' => $data['to'],
            'message' => $data['message'],
            'cost' => $cost,
            'status' => $result['status'],
            'created_at' => now(),
        ]);

        $client->refresh();
        $notifier->checkAndNotify($client);

        return back()->with('status', "SMS dispatched ({$result['status']}). Cost: ₦{$cost}.");
    }

    public function sendAirtime(Request $request, AfricasTalkingService $at, LowBalanceNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:50', 'max:50000'],
            'network' => ['nullable', 'string', 'max:50'],
        ]);

        $client = $request->user();
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
            return back()
                ->withInput()
                ->withErrors(['airtime' => "Insufficient token balance. Required: ₦{$amount}."]);
        }

        $result = $at->sendAirtime($data['phone_number'], $amount);

        if (! $result['ok']) {
            Client::query()->whereKey($client->id)->increment('token_balance', $amount);

            return back()
                ->withInput()
                ->withErrors(['airtime' => $result['message']]);
        }

        AirtimeLog::create([
            'client_id' => $client->id,
            'phone_number' => $data['phone_number'],
            'network' => $data['network'] ?: 'General',
            'amount' => $amount,
            'status' => $result['status'],
            'created_at' => now(),
        ]);

        $client->refresh();
        $notifier->checkAndNotify($client);

        return back()->with('status', "Airtime processed ({$result['status']}) for {$data['phone_number']}.");
    }

    public function exportSms(Request $request): StreamedResponse
    {
        $rows = SmsLog::query()
            ->where('client_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(5000)
            ->get(['id', 'sender_id', 'phone_number', 'message', 'cost', 'status', 'created_at']);

        return $this->streamCsv('sms_logs', ['ID', 'Sender ID', 'Phone', 'Message', 'Cost', 'Status', 'Created At'], $rows);
    }

    public function exportAirtime(Request $request): StreamedResponse
    {
        $rows = AirtimeLog::query()
            ->where('client_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(5000)
            ->get(['id', 'phone_number', 'network', 'amount', 'status', 'created_at']);

        return $this->streamCsv('airtime_logs', ['ID', 'Phone', 'Network', 'Amount', 'Status', 'Created At'], $rows);
    }

    public function exportUssd(Request $request): StreamedResponse
    {
        $rows = UssdLog::query()
            ->where('client_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(5000)
            ->get(['id', 'session_id', 'phone_number', 'service_code', 'network_name', 'hops', 'duration_ms', 'session_data', 'cost', 'status', 'created_at']);

        return $this->streamCsv('ussd_logs', ['ID', 'Session ID', 'Phone', 'Service Code', 'Carrier', 'Hops', 'Duration (ms)', 'Input Path', 'Cost', 'Status', 'Created At'], $rows);
    }

    protected function streamCsv(string $prefix, array $headers, $rows): StreamedResponse
    {
        $filename = $prefix.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_values($row->toArray()));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
