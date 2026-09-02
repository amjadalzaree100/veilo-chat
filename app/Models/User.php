<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'name_display',
        'email',
        'recovery_secret',
        'recovery_secret_hidden_at',
        'recovery_secret_updated_at',
        'is_discoverable',
        'show_public_id_on_profile',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'recovery_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'recovery_secret' => 'encrypted',
            'recovery_secret_hidden_at' => 'datetime',
            'recovery_secret_updated_at' => 'datetime',
            'is_discoverable' => 'boolean',
            'show_public_id_on_profile' => 'boolean',
        ];
    }
}
