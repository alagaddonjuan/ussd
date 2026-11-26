<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'provider',
        'endpoint',
        'status_code',
        'latency_ms',
        'outcome',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'latency_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
