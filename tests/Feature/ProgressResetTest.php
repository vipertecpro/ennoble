<?php

use App\Domain\Profile\ProgressResetService;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile as LocalProfile;
use App\Models\ProgressSnapshot;
use App\Models\Setting;
use App\Models\Statistic;
use App\NativeComponents\Screens\Settings;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = LocalProfile::factory()->onboarded()->create();
    Setting::factory()->for($this->profile)->create(['reduced_motion' => false]);

    $game = Game::query()->where('slug', 'word-match')->first();
    $level = $game->levels()->first();

    GameSession::factory()->completed()->for($this->profile)->create([
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
    ]);
    Statistic::factory()->for($this->profile)->create([
        'scope_key' => 'overall',
        'sessions_completed' => 1,
        'best_score' => 800,
    ]);
    AchievementUnlock::factory()->for($this->profile)->create([
        'achievement_id' => Achievement::query()->firstOrFail()->getKey(),
    ]);
    ProgressSnapshot::factory()->for($this->profile)->create();
});

test('the reset service wipes all local play evidence but keeps the profile', function () {
    app(ProgressResetService::class)->reset($this->profile);

    expect(GameSession::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and(Statistic::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and(AchievementUnlock::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and(ProgressSnapshot::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and(LocalProfile::query()->whereKey($this->profile->getKey())->exists())->toBeTrue();
});

test('reset requires an explicit confirmation before wiping data', function () {
    Native::visit('/settings')
        ->assertScreen(Settings::class)
        ->assertSet('resetArmed', false)
        ->assertSee('Reset stats & badges')
        ->tap('Reset stats & badges')
        ->assertSet('resetArmed', true)
        ->assertSee('Reset everything?');

    // Data is untouched until the destructive action is confirmed.
    expect(Statistic::query()->whereBelongsTo($this->profile)->count())->toBe(1);
});

test('confirming the reset clears stats and badges', function () {
    Native::visit('/settings')
        ->tap('Reset stats & badges')
        ->tap('Reset everything')
        ->assertSet('resetArmed', false)
        ->assertDontSee('Reset everything?');

    expect(Statistic::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and(AchievementUnlock::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and(GameSession::query()->whereBelongsTo($this->profile)->count())->toBe(0);
});

test('cancelling the reset leaves everything in place', function () {
    Native::visit('/settings')
        ->tap('Reset stats & badges')
        ->tap('Cancel')
        ->assertSet('resetArmed', false);

    expect(Statistic::query()->whereBelongsTo($this->profile)->count())->toBe(1);
});
