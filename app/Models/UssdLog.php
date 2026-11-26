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
        'client_price',
        'final_user_string',
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
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
