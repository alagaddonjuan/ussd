<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AnnouncementMail;
use App\Models\AirtimeLog;
use App\Models\Client;
use App\Models\PricingTier;
use App\Models\ServicePrice;
use App\Models\SmsLog;
use App\Models\Transaction;
use App\Models\UssdLog;
use App\Models\UssdMenu;
use App\Models\UssdPricing;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $totalClients = Client::query()->count();
        $totalSms = SmsLog::query()->count();
        $totalUssd = UssdLog::query()->count();
        $totalRevenue = (float) Transaction::query()
            ->whereIn(DB::raw('LOWER(status)'), ['success'])
            ->where('type', 'credit')
            ->sum('amount');

        $labels = [];
        $ussdSeries = [];
        $smsSeries = [];
        $start = Carbon::today()->subDays(6);

        $ussdCounts = UssdLog::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m-d'))
            ->map->count();

        $smsCounts = SmsLog::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m-d'))
            ->map->count();

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M d');
            $ussdSeries[] = (int) ($ussdCounts[$key] ?? 0);
            $smsSeries[] = (int) ($smsCounts[$key] ?? 0);
        }

        $recentTransactions = Transaction::query()
            ->with('client')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalClients',
            'totalSms',
            'totalUssd',
            'totalRevenue',
            'labels',
            'ussdSeries',
            'smsSeries',
            'recentTransactions'
        ));
    }

    public function clients(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->with('pricingTier')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('ussd_code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        $tiers = PricingTier::query()->orderBy('tier_name')->get();

        return view('admin.clients', compact('clients', 'tiers', 'search'));
    }

    public function updateClient(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,active,suspended'],
            'ussd_code' => ['nullable', 'string', 'max:255', 'unique:clients,ussd_code,'.$client->id],
            'sender_id' => ['nullable', 'string', 'max:255'],
            'pricing_tier_id' => ['nullable', 'integer', 'exists:pricing_tiers,id'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $client->update([
            'status' => $data['status'],
            'ussd_code' => $data['ussd_code'] ?: null,
            'sender_id' => $data['sender_id'] ?: null,
            'pricing_tier_id' => $data['pricing_tier_id'] ?: null,
            'is_admin' => (bool) ($data['is_admin'] ?? $client->is_admin),
        ]);

        return back()->with('status', "Updated configuration for {$client->name}.");
    }

    public function manualTransaction(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $type = $data['type'];

        $ok = DB::transaction(function () use ($client, $type, $amount, $data): bool {
            $locked = Client::query()->whereKey($client->id)->lockForUpdate()->first();
            if (! $locked) {
                return false;
            }

            if ($type === 'debit' && (float) $locked->token_balance < $amount) {
                return false;
            }

            if ($type === 'credit') {
                $locked->increment('token_balance', $amount);
            } else {
                $locked->decrement('token_balance', $amount);
            }

            Transaction::create([
                'client_id' => $locked->id,
                'type' => $type,
                'reference' => 'admin_'.$type.'_'.now()->timestamp.'_'.random_int(100, 999),
                'amount' => $amount,
                'tokens_purchased' => $type === 'credit' ? (int) round($amount) : 0,
                'status' => 'success',
                'gateway' => $type === 'credit' ? 'admin_manual' : 'admin_debit',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
            ]);

            return true;
        });

        if (! $ok) {
            return back()->withErrors(['amount' => 'Insufficient balance for manual debit.']);
        }

        return back()->with('status', "Manual {$type} of ₦{$amount} applied to {$client->name}.");
    }

    public function pricingTiers(): View
    {
        $tiers = PricingTier::query()->with('servicePrices')->withCount('clients')->orderBy('id')->get();
        $telcoRates = UssdPricing::query()->orderBy('network_name')->get();

        return view('admin.pricing-tiers', compact('tiers', 'telcoRates'));
    }

    public function storeTier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tier_name' => ['required', 'string', 'max:255', 'unique:pricing_tiers,tier_name'],
            'description' => ['nullable', 'string', 'max:500'],
            'sms_price' => ['required', 'numeric', 'min:0'],
            'ussd_price' => ['required', 'numeric', 'min:0'],
            'airtime_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data): void {
            $tier = PricingTier::create([
                'tier_name' => $data['tier_name'],
                'description' => $data['description'] ?? null,
            ]);

            ServicePrice::insert([
                ['pricing_tier_id' => $tier->id, 'service_name' => 'SMS', 'price' => $data['sms_price']],
                ['pricing_tier_id' => $tier->id, 'service_name' => 'USSD', 'price' => $data['ussd_price']],
                ['pricing_tier_id' => $tier->id, 'service_name' => 'AIRTIME', 'price' => $data['airtime_price']],
            ]);
        });

        return back()->with('status', "Pricing tier \"{$data['tier_name']}\" created.");
    }

    public function updateTierPrices(Request $request, PricingTier $tier): RedirectResponse
    {
        $data = $request->validate([
            'sms_price' => ['required', 'numeric', 'min:0'],
            'ussd_price' => ['required', 'numeric', 'min:0'],
            'airtime_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($tier, $data): void {
            foreach (['SMS' => $data['sms_price'], 'USSD' => $data['ussd_price'], 'AIRTIME' => $data['airtime_price']] as $service => $price) {
                ServicePrice::updateOrCreate(
                    ['pricing_tier_id' => $tier->id, 'service_name' => $service],
                    ['price' => $price]
                );
            }
        });

        return back()->with('status', "Updated service prices for {$tier->tier_name}.");
    }

    public function destroyTier(PricingTier $tier): RedirectResponse
    {
        if ($tier->clients()->exists()) {
            return back()->withErrors(['tier' => 'Cannot delete tier while clients are assigned to it.']);
        }

        $tier->delete();

        return back()->with('status', "Deleted pricing tier \"{$tier->tier_name}\".");
    }

    public function storeTelcoRate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'network_code' => ['required', 'string', 'max:50'],
            'network_name' => ['required', 'string', 'max:255'],
            'tokens_per_interval' => ['required', 'integer', 'min:0'],
        ]);

        UssdPricing::updateOrCreate(
            ['network_code' => $data['network_code']],
            [
                'network_name' => $data['network_name'],
                'tokens_per_interval' => $data['tokens_per_interval'],
            ]
        );

        return back()->with('status', "Saved telco USSD rate for {$data['network_name']}.");
    }

    public function logs(Request $request): View
    {
        $tab = $request->query('tab', 'ussd');

        $ussdLogs = UssdLog::query()->with('client')->orderByDesc('id')->limit(50)->get();
        $smsLogs = SmsLog::query()->with('client')->orderByDesc('id')->limit(50)->get();
        $airtimeLogs = AirtimeLog::query()->with('client')->orderByDesc('id')->limit(50)->get();
        $transactions = Transaction::query()->with('client')->orderByDesc('id')->limit(50)->get();

        return view('admin.logs', compact('tab', 'ussdLogs', 'smsLogs', 'airtimeLogs', 'transactions'));
    }

    public function exportCsv(Request $request, string $type): StreamedResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = match ($type) {
            'sms' => SmsLog::query(),
            'airtime' => AirtimeLog::query(),
            'ussd' => UssdLog::query(),
            default => abort(404),
        };

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"]);
        } else {
            $query->limit(5000);
        }

        $rows = $query->orderByDesc('id')->get();
        $filename = "admin_{$type}_report_".now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) {
                fputcsv($out, array_keys($rows->first()->toArray()));
                foreach ($rows as $row) {
                    fputcsv($out, array_values($row->toArray()));
                }
            } else {
                fputcsv($out, ['No records found']);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function ussdMenus(): View
    {
        $menus = UssdMenu::query()
            ->with('client')
            ->withCount('items')
            ->orderByDesc('id')
            ->get();

        return view('admin.ussd-menus', compact('menus'));
    }

    public function announcements(): View
    {
        $activeCount = Client::query()->where('status', 'active')->count();

        return view('admin.announcements', compact('activeCount'));
    }

    public function sendAnnouncement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $emails = Client::query()->where('status', 'active')->pluck('email');

        foreach ($emails as $email) {
            Mail::to($email)->send(new AnnouncementMail($data['subject'], $data['message']));
        }

        return back()->with('status', "Announcement dispatched to {$emails->count()} active client(s).");
    }
}
