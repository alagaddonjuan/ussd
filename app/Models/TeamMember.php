<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $table = 'team_members';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'team_id',
        'client_id',
        'role_id',
        'status',
        'invitation_token',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
