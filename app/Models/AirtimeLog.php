<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirtimeLog extends Model
{
    protected $table = 'airtime_logs';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'phone_number',
        'network',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
