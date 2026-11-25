<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UssdMenu extends Model
{
    protected $table = 'ussd_menus';
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'menu_name',
        'is_active',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UssdMenuItem::class, 'menu_id')->orderBy('id');
    }
}
