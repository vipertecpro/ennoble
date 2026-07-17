<?php

namespace App\Domain\Games\Contracts;

use App\Models\GameRound;
use Illuminate\Support\Collection;

interface GameScoringService
{
    /**
     * Calculate a deterministic result from persisted round evidence.
     *
     * @param  Collection<int, GameRound>  $rounds
     */
    public function score(Collection $rounds): ScoringResult;
}
