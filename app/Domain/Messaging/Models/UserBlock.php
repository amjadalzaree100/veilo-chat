<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlock extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'user_blocks';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'blocker_id',
        'blocked_id',
    ];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}
