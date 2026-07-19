<?php

use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile as LocalProfile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
use Carbon\CarbonImmutable;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-18 09:30:00');

    $this->profile = LocalProfile::factory()->onboarded()->create();
    Setting::factory()->for($this->profile)->create(['reduced_motion' => false]);

    $this->game = Game::query()->where('slug', 'word-match')->first();
    $this->level = $this->game->levels()->first();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('game detail hides the results log until a session is completed', function () {
    Native::visit('/games/word-match')
        ->assertScreen(GameDetail::class)
        ->assertSee('How to play')
        ->assertDontSee('Recent results');
});

test('game detail lists recent completed sessions with their results', function () {
    GameSession::factory()->completed()->for($this->profile)->create([
        'game_id' => $this->game->getKey(),
        'game_level_id' => $this->level->getKey(),
        'score' => 1240,
        'accuracy' => 80,
        'correct_count' => 8,
        'incorrect_count' => 2,
        'average_response_ms' => 1500,
        'started_at' => now()->subSeconds(75),
        'completed_at' => now(),
    ]);

    Native::visit('/games/word-match')
        ->assertSee('Recent results')
        ->assertSee('1,240 pts')
        ->assertSee('8/10 correct')
        ->assertSee('80% acc')
        ->assertSee('1.5s/q')
        ->assertSee('1m 15s')
        ->assertAccessible();
});

test('the results log is scoped to the game being viewed', function () {
    $quickMath = Game::query()->where('slug', 'quick-math')->first();

    GameSession::factory()->completed()->for($this->profile)->create([
        'game_id' => $quickMath->getKey(),
        'game_level_id' => $quickMath->levels()->first()->getKey(),
        'score' => 999,
    ]);

    Native::visit('/games/word-match')
        ->assertDontSee('Recent results')
        ->assertDontSee('999');
});
