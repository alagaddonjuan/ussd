<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'clients';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_2fa_enabled',
        'two_fa_secret',
        'ussd_code',
        'token_balance',
        'sender_id',
        'is_admin',
        'status',
        'pricing_tier_id',
        'password_reset_token',
        'password_reset_expires',
    ];

    protected $hidden = [
        'password',
        'two_fa_secret',
        'password_reset_token',
    ];

    protected function casts(): array
    {
        return [
            'is_2fa_enabled' => 'boolean',
            'is_admin' => 'boolean',
            'token_balance' => 'float',
            'created_at' => 'datetime',
            'password_reset_expires' => 'datetime',
        ];
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function pricingTier(): BelongsTo
    {
        return $this->belongsTo(PricingTier::class, 'pricing_tier_id');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'client_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'client_id');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'client_id');
    }

    public function airtimeLogs(): HasMany
    {
        return $this->hasMany(AirtimeLog::class, 'client_id');
    }

    public function ussdLogs(): HasMany
    {
        return $this->hasMany(UssdLog::class, 'client_id');
    }

    public function ussdMenus(): HasMany
    {
        return $this->hasMany(UssdMenu::class, 'client_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'client_id');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'client_id');
    }

    public function getServicePrice(string $serviceName, float $default = 1.0): float
    {
        if (! $this->pricing_tier_id) {
            return $default;
        }

        $record = ServicePrice::query()
            ->where('pricing_tier_id', $this->pricing_tier_id)
            ->where('service_name', strtoupper($serviceName))
            ->first();

        return $record ? (float) $record->price : $default;
    }

    /**
     * Resolve the USSD billing multiplier applied to Africa's Talking costs.
     */
    public function getUssdCostMultiplier(): float
    {
        $defaultMultiplier = (float) config('services.ussd.cost_multiplier', 4.0);
        $tierName = strtolower(trim((string) ($this->pricingTier?->tier_name ?? '')));

        if ($tierName === '') {
            return $defaultMultiplier;
        }

        if (str_contains($tierName, 'max') || str_contains($tierName, 'platinum')) {
            return 2.5;
        }

        if (str_contains($tierName, 'premium')) {
            return 2.9;
        }

        if (str_contains($tierName, 'plus') && ! str_contains($tierName, 'basic')) {
            return 3.5;
        }

        return $defaultMultiplier;
    }
}
