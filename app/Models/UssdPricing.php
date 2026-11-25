<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UssdPricing extends Model
{
    protected $table = 'ussd_pricing';
    public $timestamps = false;

    protected $fillable = [
        'network_code',
        'network_name',
        'tokens_per_interval',
    ];

    protected function casts(): array
    {
        return [
            'tokens_per_interval' => 'integer',
        ];
    }
}
