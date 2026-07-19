<?php

namespace App\Domain\Profile;

use App\Models\AchievementUnlock;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\ProgressSnapshot;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;

/**
 * Wipe a profile's local play evidence for a clean slate: statistics, earned
 * badges, skill snapshots and every game session (rounds cascade). The profile
 * identity and settings are preserved.
 */
final class ProgressResetService
{
    public function reset(Profile $profile): void
    {
        DB::transaction(function () use ($profile): void {
            // Rounds and session-scoped snapshots cascade on the session delete.
            GameSession::query()->whereBelongsTo($profile)->delete();
            AchievementUnlock::query()->whereBelongsTo($profile)->delete();
            ProgressSnapshot::query()->whereBelongsTo($profile)->delete();
            Statistic::query()->whereBelongsTo($profile)->delete();
        });
    }
}
