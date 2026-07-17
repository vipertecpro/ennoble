<?php

use App\Enums\ClearThoughtMode;
use App\Enums\Difficulty;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Enums\ThemePreference;
use App\Enums\WorkoutStatus;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\Challenge;
use App\Models\DailyWorkout;
use App\Models\DailyWorkoutItem;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;

test('domain models expose typed casts and complete relationships', function () {
    $profile = Profile::factory()->create();
    $setting = Setting::factory()->for($profile)->create([
        'theme_preference' => ThemePreference::Dark,
    ]);
    $game = Game::query()->where('type', GameType::ClearThought)->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();
    $challenge = Challenge::factory()->create([
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
    ]);
    $workout = DailyWorkout::factory()->for($profile)->create();
    $item = DailyWorkoutItem::factory()->create([
        'daily_workout_id' => $workout->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
    ]);
    $session = GameSession::factory()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
        'daily_workout_item_id' => $item->getKey(),
    ]);
    $round = GameRound::factory()->create([
        'game_session_id' => $session->getKey(),
        'challenge_id' => $challenge->getKey(),
    ]);
    $achievement = Achievement::query()->where('slug', 'first-step')->firstOrFail();
    $unlock = AchievementUnlock::factory()->create([
        'profile_id' => $profile->getKey(),
        'achievement_id' => $achievement->getKey(),
        'game_session_id' => $session->getKey(),
        'daily_workout_id' => $workout->getKey(),
    ]);

    expect($profile->difficulty_preference)->toBe(Difficulty::Intermediate)
        ->and($setting->theme_preference)->toBe(ThemePreference::Dark)
        ->and($game->type)->toBe(GameType::ClearThought)
        ->and($level->difficulty)->toBe(Difficulty::Intermediate)
        ->and($challenge->mode)->toBe(ClearThoughtMode::RemoveUnnecessaryWords)
        ->and($workout->status)->toBe(WorkoutStatus::Pending)
        ->and($session->status)->toBe(SessionStatus::InProgress)
        ->and($round->outcome)->toBe(RoundOutcome::Correct)
        ->and($profile->setting->is($setting))->toBeTrue()
        ->and($profile->dailyWorkouts->contains($workout))->toBeTrue()
        ->and($workout->items->contains($item))->toBeTrue()
        ->and($item->sessions->contains($session))->toBeTrue()
        ->and($session->rounds->contains($round))->toBeTrue()
        ->and($round->challenge->is($challenge))->toBeTrue()
        ->and($unlock->achievement->is($achievement))->toBeTrue()
        ->and($unlock->dailyWorkout->is($workout))->toBeTrue();
});
