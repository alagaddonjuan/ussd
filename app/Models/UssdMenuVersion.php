<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UssdMenuVersion extends Model
{
    protected $table = 'ussd_menu_versions';
    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'version_number',
        'label',
        'outline_text',
        'snapshot_json',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(UssdMenu::class, 'menu_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'created_by');
    }
}
