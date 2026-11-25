<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\Transaction;
use App\Models\UssdLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user();

        $totalSms = SmsLog::query()->where('client_id', $client->id)->count();
        $totalUssd = UssdLog::query()->where('client_id', $client->id)->count();
        $totalAirtime = $client->airtimeLogs()->count();
        $smsRate = $client->getServicePrice('SMS', 3.50);
        $ussdRate = $client->getServicePrice('USSD', 3.50);

        $labels = [];
        $ussdSeries = [];
        $smsSeries = [];

        $start = Carbon::today()->subDays(6);

        $ussdCounts = UssdLog::query()
            ->where('client_id', $client->id)
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($log) => Carbon::parse($log->created_at)->format('Y-m-d'))
            ->map->count();

        $smsCounts = SmsLog::query()
            ->where('client_id', $client->id)
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($log) => Carbon::parse($log->created_at)->format('Y-m-d'))
            ->map->count();

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M d');
            $ussdSeries[] = (int) ($ussdCounts[$key] ?? 0);
            $smsSeries[] = (int) ($smsCounts[$key] ?? 0);
        }

        $recentTransactions = Transaction::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $recentUssdLogs = UssdLog::query()
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return view('client.dashboard', compact(
            'client',
            'totalSms',
            'totalUssd',
            'totalAirtime',
            'smsRate',
            'ussdRate',
            'labels',
            'ussdSeries',
            'smsSeries',
            'recentTransactions',
            'recentUssdLogs'
        ));
    }
}
