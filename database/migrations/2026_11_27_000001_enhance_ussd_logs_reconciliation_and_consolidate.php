<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ussd_logs')) {
            Schema::table('ussd_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('ussd_logs', 'reconciled_at')) {
                    $table->timestamp('reconciled_at')->nullable();
                }
                if (! Schema::hasColumn('ussd_logs', 'gateway_error')) {
                    $table->text('gateway_error')->nullable();
                }
                if (! Schema::hasColumn('ussd_logs', 'last_app_response')) {
                    $table->text('last_app_response')->nullable();
                }
            });

            try {
                Schema::table('ussd_logs', function (Blueprint $table) {
                    $table->index(['client_id', 'session_id'], 'ussd_logs_client_session_idx');
                });
            } catch (\Throwable) {
                // Index may already exist on migrated environments.
            }

            $this->consolidateExistingSessions();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ussd_logs')) {
            try {
                Schema::table('ussd_logs', function (Blueprint $table) {
                    $table->dropIndex('ussd_logs_client_session_idx');
                });
            } catch (\Throwable) {
            }

            Schema::table('ussd_logs', function (Blueprint $table) {
                foreach (['reconciled_at', 'gateway_error', 'last_app_response'] as $col) {
                    if (Schema::hasColumn('ussd_logs', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    protected function consolidateExistingSessions(): void
    {
        $duplicateGroups = DB::table('ussd_logs')
            ->select('client_id', 'session_id')
            ->whereNotNull('session_id')
            ->where('session_id', '!=', '')
            ->groupBy('client_id', 'session_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $rows = DB::table('ussd_logs')
                ->where('client_id', $group->client_id)
                ->where('session_id', $group->session_id)
                ->orderBy('id')
                ->get();

            if ($rows->count() <= 1) {
                continue;
            }

            $keeper = $rows->sortByDesc(function ($r) {
                return [
                    (float) ($r->upstream_cost ?? 0) > 0 ? 1 : 0,
                    $r->duration_ms !== null ? 1 : 0,
                    (float) ($r->client_price ?? $r->cost ?? 0),
                    (int) $r->id,
                ];
            })->first();

            $latest = $rows->last();
            $totalCost = round((float) $rows->sum(fn ($r) => (float) ($r->cost ?? 0)), 2);
            $totalClientPrice = round((float) $rows->sum(fn ($r) => (float) ($r->client_price ?? $r->cost ?? 0)), 2);
            $maxUpstream = round((float) $rows->max(fn ($r) => (float) ($r->upstream_cost ?? 0)), 4);
            $durationMs = $rows->pluck('duration_ms')->filter(fn ($v) => $v !== null)->last();
            $maxHops = max($rows->count(), (int) $rows->max(fn ($r) => (int) ($r->hops ?? 1)));
            $networkName = $rows->pluck('network_name')->filter()->last();
            $isReconciled = $maxUpstream > 0 || $durationMs !== null || stripos((string) $keeper->status, 'incomplete') !== false;

            DB::table('ussd_logs')
                ->where('id', $keeper->id)
                ->update([
                    'cost' => $totalCost,
                    'client_price' => $totalClientPrice,
                    'upstream_cost' => $maxUpstream,
                    'duration_ms' => $durationMs,
                    'hops' => $maxHops,
                    'network_name' => $networkName ?: $keeper->network_name,
                    'session_data' => $latest->session_data ?: $keeper->session_data,
                    'final_user_string' => $latest->final_user_string ?: $keeper->final_user_string,
                    'reconciled_at' => $isReconciled ? ($keeper->logged_at ?? $keeper->created_at ?? now()) : null,
                ]);

            DB::table('ussd_logs')
                ->where('client_id', $group->client_id)
                ->where('session_id', $group->session_id)
                ->where('id', '!=', $keeper->id)
                ->delete();
        }

        DB::table('ussd_logs')
            ->whereNull('reconciled_at')
            ->where(function ($q) {
                $q->where('upstream_cost', '>', 0)
                    ->orWhereNotNull('duration_ms');
            })
            ->update([
                'reconciled_at' => DB::raw('COALESCE(logged_at, created_at)'),
            ]);
    }
};
