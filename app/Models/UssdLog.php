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
}
