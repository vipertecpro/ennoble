<?php

namespace App\Domain\Progress;

use App\Enums\SkillKey;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\ProgressSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ProgressService
{
    /**
     * Apply bounded skill changes and append historical progress snapshots.
     *
     * @param  array<string, int>  $skillDeltas
     * @return Collection<int, ProgressSnapshot>
     */
    public function updateSkillValues(
        Profile $profile,
        array $skillDeltas,
        ?GameSession $gameSession = null,
    ): Collection {
        if ($gameSession !== null && $gameSession->profile_id !== $profile->getKey()) {
            throw new LogicException('Progress evidence must belong to the same profile.');
        }

        return DB::transaction(function () use ($profile, $skillDeltas, $gameSession): Collection {
            $snapshots = collect();

            foreach ($skillDeltas as $skill => $requestedDelta) {
                $skillKey = SkillKey::from($skill);

                if ($gameSession !== null) {
                    $existingSnapshot = ProgressSnapshot::query()
                        ->whereBelongsTo($gameSession)
                        ->forSkill($skillKey)
                        ->first();

                    if ($existingSnapshot !== null) {
                        $snapshots->push($existingSnapshot);

                        continue;
                    }
                }

                $previousSnapshot = ProgressSnapshot::query()
                    ->whereBelongsTo($profile)
                    ->forSkill($skillKey)
                    ->latest('recorded_at')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();
                $scoreBefore = $previousSnapshot?->score_after ?? 500;
                $scoreAfter = max(0, min(1000, $scoreBefore + $requestedDelta));

                $snapshots->push(ProgressSnapshot::query()->create([
                    'profile_id' => $profile->getKey(),
                    'game_session_id' => $gameSession?->getKey(),
                    'skill_key' => $skillKey,
                    'score_before' => $scoreBefore,
                    'score_after' => $scoreAfter,
                    'delta' => $scoreAfter - $scoreBefore,
                    'evidence_count' => ($previousSnapshot?->evidence_count ?? 0) + 1,
                    'recorded_at' => now(),
                ]));
            }

            return $snapshots;
        });
    }

    /**
     * Return only skill values backed by persisted evidence.
     *
     * @return array<string, int>
     */
    public function currentSkillValues(Profile $profile): array
    {
        return ProgressSnapshot::query()
            ->whereBelongsTo($profile)
            ->latest('recorded_at')
            ->latest('id')
            ->get()
            ->unique(fn (ProgressSnapshot $snapshot): string => $snapshot->skill_key->value)
            ->mapWithKeys(fn (ProgressSnapshot $snapshot): array => [
                $snapshot->skill_key->value => $snapshot->score_after,
            ])
            ->all();
    }

    /**
     * Retrieve recent history for a single trained skill.
     *
     * @return Collection<int, ProgressSnapshot>
     */
    public function history(Profile $profile, SkillKey $skillKey, int $limit = 30): Collection
    {
        return ProgressSnapshot::query()
            ->whereBelongsTo($profile)
            ->forSkill($skillKey)
            ->latest('recorded_at')
            ->limit(max(1, $limit))
            ->get();
    }
}
