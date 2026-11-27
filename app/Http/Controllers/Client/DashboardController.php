<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SmsLog;
use App\Models\Transaction;
use App\Models\UssdLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->buildDashboardData($request->user());

        return view('client.dashboard', $data);
    }

    public function live(Request $request): JsonResponse
    {
        $data = $this->buildDashboardData($request->user());
        $recentUssdHtml = view('client.partials._dashboard-ussd-rows', [
            'recentUssdLogs' => $data['recentUssdLogs'],
        ])->render();

        return response()->json([
            'token_balance' => '₦'.number_format((float) $data['client']->token_balance, 2),
            'total_ussd' => number_format($data['totalUssd']),
            'completion_rate' => $data['completionRate'].'%',
            'completed_sessions' => number_format($data['completedSessions']),
            'incomplete_sessions' => number_format($data['incompleteSessions']),
            'avg_duration_sec' => $data['avgDurationSec'].'s',
            'avg_hops' => $data['avgHops'].' hops',
            'recent_ussd_html' => $recentUssdHtml,
            'updated_at' => now()->format('H:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDashboardData(Client $client): array
    {
        $totalSms = SmsLog::query()->where('client_id', $client->id)->count();
        $totalUssd = UssdLog::query()->where('client_id', $client->id)->count();
        $totalAirtime = $client->airtimeLogs()->count();
        $smsRate = $client->getServicePrice('SMS', 3.50);
        $telcoRates = $client->getTelcoUssdRates();
        $ussdRate = $client->getServicePrice('USSD', $telcoRates['mtn']['rate']);

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

        $allClientUssd = UssdLog::query()
            ->where('client_id', $client->id)
            ->get(['status', 'duration_ms', 'hops', 'network_name']);

        $completedSessions = $allClientUssd->filter(function ($row) {
            $st = strtolower((string) $row->status);

            return ! str_contains($st, 'incomplete') && ! str_contains($st, 'fail') && ! str_contains($st, 'reject');
        })->count();
        $incompleteSessions = max(0, $allClientUssd->count() - $completedSessions);
        $completionRate = $allClientUssd->isNotEmpty()
            ? round(($completedSessions / $allClientUssd->count()) * 100, 1)
            : 100.0;
        $avgDurationSec = $allClientUssd->whereNotNull('duration_ms')->isNotEmpty()
            ? round(((float) $allClientUssd->whereNotNull('duration_ms')->avg('duration_ms')) / 1000, 1)
            : 0.0;
        $avgHops = $allClientUssd->whereNotNull('hops')->isNotEmpty()
            ? round((float) $allClientUssd->whereNotNull('hops')->avg('hops'), 1)
            : 0.0;
        $carrierBreakdown = $allClientUssd
            ->groupBy(fn ($row) => $row->network_name ?: 'Other')
            ->map->count()
            ->sortDesc()
            ->all();

        return compact(
            'client',
            'totalSms',
            'totalUssd',
            'totalAirtime',
            'smsRate',
            'ussdRate',
            'telcoRates',
            'labels',
            'ussdSeries',
            'smsSeries',
            'recentTransactions',
            'recentUssdLogs',
            'completedSessions',
            'incompleteSessions',
            'completionRate',
            'avgDurationSec',
            'avgHops',
            'carrierBreakdown'
        );
    }
}
