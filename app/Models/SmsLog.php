<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $table = 'sms_logs';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'sender_id',
        'phone_number',
        'message',
        'cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
