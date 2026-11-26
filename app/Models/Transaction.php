<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $table = 'transactions';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'type',
        'reference',
        'amount',
        'tokens_purchased',
        'status',
        'gateway',
        'notes',
        'perfex_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'tokens_purchased' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
