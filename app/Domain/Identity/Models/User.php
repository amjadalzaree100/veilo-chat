<?php

namespace App\Domain\Identity\Models;

use App\Domain\Authentication\Models\Device;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'public_id',
        'username',
        'display_name',
        'email',
        'email_verified_at',
        'recovery_secret_encrypted',
        'recovery_secret_hidden_at',
        'privacy',
    ];

    protected $hidden = [
        'recovery_secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'recovery_secret_encrypted' => 'encrypted',
            'recovery_secret_hidden_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
