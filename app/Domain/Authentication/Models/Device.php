<?php

namespace App\Domain\Authentication\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $table = 'devices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'device_identifier',
        'device_secret_hash',
        'device_name',
        'platform',
        'push_token',
        'last_active_at',
        'is_primary',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'immutable_datetime',
            'is_primary' => 'boolean',
            'revoked_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    protected $hidden = [
        'device_secret_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
