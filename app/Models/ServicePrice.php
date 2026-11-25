<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePrice extends Model
{
    protected $table = 'service_prices';
    public $timestamps = false;

    protected $fillable = [
        'pricing_tier_id',
        'service_name',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
        ];
    }

    public function pricingTier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class, 'pricing_tier_id');
    }
}
