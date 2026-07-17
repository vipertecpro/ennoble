<?php

namespace App\Models;

use App\Enums\RoundOutcome;
use Database\Factories\GameRoundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRound extends Model
{
    /** @use HasFactory<GameRoundFactory> */
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'challenge_id',
        'round_number',
        'outcome',
        'response_ms',
        'score_delta',
        'combo',
        'hint_used',
        'response',
    ];

    protected $attributes = [
        'score_delta' => 0,
        'hint_used' => false,
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    protected function casts(): array
    {
        return [
            'outcome' => RoundOutcome::class,
            'hint_used' => 'boolean',
            'response' => 'array',
        ];
    }
}
