<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UssdLog extends Model
{
    protected $table = 'ussd_logs';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'phone_number',
        'session_id',
        'service_code',
        'session_data',
        'cost',
        'upstream_cost',
        'duration_ms',
        'hops',
        'network_name',
        'status',
        'logged_at',
        'reconciled_at',
        'client_price',
        'final_user_string',
        'gateway_error',
        'last_app_response',
        'hops_trace',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'float',
            'upstream_cost' => 'float',
            'client_price' => 'float',
            'duration_ms' => 'integer',
            'hops' => 'integer',
            'created_at' => 'datetime',
            'logged_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'hops_trace' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function isReconciled(): bool
    {
        return $this->reconciled_at !== null || (float) ($this->upstream_cost ?? 0) > 0;
    }

    public function effectiveBilled(): float
    {
        return round((float) ($this->client_price ?? $this->cost ?? 0), 2);
    }

    public function margin(): float
    {
        return round($this->effectiveBilled() - (float) ($this->upstream_cost ?? 0), 2);
    }

    /**
     * Return recorded step-by-step hop entries, or synthesize a fallback trace for legacy/event-only logs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function normalizedHopsTrace(): array
    {
        if (is_array($this->hops_trace) && ! empty($this->hops_trace)) {
            return array_values($this->hops_trace);
        }

        $startTime = optional($this->created_at ?? $this->logged_at)->format('M d, Y g:i A') ?: '—';
        $rawPath = trim((string) ($this->final_user_string ?? $this->session_data ?? ''));
        $segments = ($rawPath === '' || $rawPath === 'Main Menu' || $rawPath === 'Session Event' || $rawPath === 'Verified via AT CSV')
            ? []
            : explode('*', $rawPath);

        $totalHops = max(1, (int) ($this->hops ?? 1));
        $trace = [];

        if (! empty($segments)) {
            $trace[] = [
                'hop' => 1,
                'display_time' => $startTime,
                'input' => 'Dialed '.($this->service_code ?: 'USSD'),
                'app_response' => 'Main Menu screen presented',
                'error_message' => null,
                'status' => 'Success',
            ];

            $cumulative = [];
            foreach ($segments as $idx => $seg) {
                $cumulative[] = $seg;
                $isLast = $idx === array_key_last($segments);
                $trace[] = [
                    'hop' => $idx + 2,
                    'display_time' => $startTime,
                    'input' => implode('*', $cumulative),
                    'app_response' => $isLast && $this->last_app_response ? $this->last_app_response : 'Menu step processed',
                    'error_message' => $isLast ? ($this->gateway_error ?: null) : null,
                    'status' => $isLast ? ($this->status ?: 'Success') : 'Success',
                ];
            }

            return $trace;
        }

        for ($h = 1; $h <= $totalHops; $h++) {
            $isLast = $h === $totalHops;
            $trace[] = [
                'hop' => $h,
                'display_time' => $startTime,
                'input' => $h === 1 ? 'Dialed '.($this->service_code ?: 'USSD') : "Step {$h}",
                'app_response' => $isLast && $this->last_app_response ? $this->last_app_response : ($this->session_data ?: '—'),
                'error_message' => $isLast ? ($this->gateway_error ?: null) : null,
                'status' => $isLast ? ($this->status ?: 'Success') : 'Success',
            ];
        }

        return $trace;
    }
}
