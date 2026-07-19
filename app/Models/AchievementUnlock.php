<?php

namespace App\Models;

use Database\Factories\AchievementUnlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchievementUnlock extends Model
{
    /** @use HasFactory<AchievementUnlockFactory> */
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'achievement_id',
        'game_session_id',
        'unlocked_at',
        'evidence',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            'evidence' => 'array',
        ];
    }
}
