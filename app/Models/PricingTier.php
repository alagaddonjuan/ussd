<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingTier extends Model
{
    protected $table = 'pricing_tiers';
    public $timestamps = false;

    protected $fillable = [
        'tier_name',
        'description',
    ];

    public function servicePrices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'pricing_tier_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'pricing_tier_id');
    }
}
