<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $table = 'api_keys';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'key_name',
        'api_key',
        'whitelisted_ips',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
