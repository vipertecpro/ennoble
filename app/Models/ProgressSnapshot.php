<?php

namespace App\Models;

use App\Enums\SkillKey;
use Database\Factories\ProgressSnapshotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressSnapshot extends Model
{
    /** @use HasFactory<ProgressSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'game_session_id',
        'skill_key',
        'score_before',
        'score_after',
        'delta',
        'evidence_count',
        'recorded_at',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function scopeForSkill(Builder $query, SkillKey $skill): Builder
    {
        return $query->where('skill_key', $skill);
    }

    protected function casts(): array
    {
        return [
            'skill_key' => SkillKey::class,
            'recorded_at' => 'datetime',
        ];
    }
}
