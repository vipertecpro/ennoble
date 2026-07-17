<?php

namespace App\Domain\Profile;

use App\Enums\Difficulty;
use App\Enums\TrainingGoal;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ProfileService
{
    public const DISPLAY_NAME_MAX_LENGTH = 40;

    /**
     * Create or update Ennoble's single local profile and ensure settings exist.
     */
    public function createOrUpdate(
        ?string $displayName,
        TrainingGoal $trainingGoal = TrainingGoal::Balanced,
        Difficulty $difficulty = Difficulty::Intermediate,
    ): Profile {
        $normalizedDisplayName = Str::squish($displayName ?? '');

        if (Str::length($normalizedDisplayName) > self::DISPLAY_NAME_MAX_LENGTH) {
            throw new InvalidArgumentException(
                'The local display name may not be longer than '.self::DISPLAY_NAME_MAX_LENGTH.' characters.',
            );
        }

        return DB::transaction(function () use ($normalizedDisplayName, $trainingGoal, $difficulty): Profile {
            $profile = Profile::query()->updateOrCreate(
                ['singleton_key' => 'local'],
                [
                    'display_name' => $normalizedDisplayName,
                    'training_goal' => $trainingGoal,
                    'difficulty_preference' => $difficulty,
                ],
            );

            $profile->setting()->firstOrCreate();

            return $profile->load('setting');
        });
    }

    /**
     * Retrieve the single local profile when one has been created.
     */
    public function current(): ?Profile
    {
        return Profile::query()
            ->where('singleton_key', 'local')
            ->with('setting')
            ->first();
    }
}
