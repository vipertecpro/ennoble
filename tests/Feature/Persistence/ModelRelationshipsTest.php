<?php

use App\Enums\Difficulty;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Enums\ThemePreference;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\Challenge;
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
    $game = Game::query()->where('type', GameType::WordMatch)->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();
    $challenge = Challenge::factory()->create([
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
        'mode' => 'match',
    ]);
    $session = GameSession::factory()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
    ]);
    $round = GameRound::factory()->create([
        'game_session_id' => $session->getKey(),
        'challenge_id' => $challenge->getKey(),
    ]);
    $achievement = Achievement::query()->orderBy('sort_order')->firstOrFail();
    $unlock = AchievementUnlock::factory()->create([
        'profile_id' => $profile->getKey(),
        'achievement_id' => $achievement->getKey(),
        'game_session_id' => $session->getKey(),
    ]);

    expect($profile->difficulty_preference)->toBe(Difficulty::Intermediate)
        ->and($setting->theme_preference)->toBe(ThemePreference::Dark)
        ->and($game->type)->toBe(GameType::WordMatch)
        ->and($level->difficulty)->toBe(Difficulty::Intermediate)
        ->and($session->status)->toBe(SessionStatus::InProgress)
        ->and($round->outcome)->toBe(RoundOutcome::Correct)
        ->and($profile->setting->is($setting))->toBeTrue()
        ->and($session->game->is($game))->toBeTrue()
        ->and($session->level->is($level))->toBeTrue()
        ->and($session->rounds->contains($round))->toBeTrue()
        ->and($round->challenge->is($challenge))->toBeTrue()
        ->and($unlock->achievement->is($achievement))->toBeTrue()
        ->and($unlock->gameSession->is($session))->toBeTrue();
});
