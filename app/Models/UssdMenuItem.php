<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UssdMenuItem extends Model
{
    protected $table = 'ussd_menu_items';
    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'parent_item_id',
        'option_trigger',
        'response_type',
        'response_text',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(UssdMenu::class, 'menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_item_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_item_id')->orderBy('id');
    }

    public function displayBody(): string
    {
        $body = $this->response_text ?? '';
        if (str_contains($body, '|')) {
            $parts = explode('|', $body, 2);

            return $parts[1] !== '' ? $parts[1] : $parts[0];
        }

        return $body;
    }

    public function displayTitle(): ?string
    {
        $body = $this->response_text ?? '';
        if (str_contains($body, '|')) {
            return explode('|', $body, 2)[0];
        }

        return null;
    }
}
