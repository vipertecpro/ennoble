<?php

use App\Domain\Progression\LevelService;
use App\Models\Statistic;

test('level starts at 1 with zero xp', function () {
    $breakdown = app(LevelService::class)->breakdown(0);

    expect($breakdown['level'])->toBe(1)
        ->and($breakdown['into'])->toBe(0)
        ->and($breakdown['progress'])->toBe(0.0)
        ->and($breakdown['title'])->toBe('Warming up');
});

test('crossing a level span advances the level', function () {
    $service = app(LevelService::class);

    expect($service->breakdown(119)['level'])->toBe(1)
        ->and($service->breakdown(120)['level'])->toBe(2)
        ->and($service->breakdown(120)['into'])->toBe(0)
        ->and($service->breakdown(120)['toNext'])->toBe(180);
});

test('xp is derived from completed sessions and correct answers', function () {
    $statistic = new Statistic;
    $statistic->sessions_completed = 4;
    $statistic->correct_count = 10;

    expect(app(LevelService::class)->totalXp($statistic))->toBe(4 * 25 + 10 * 3)
        ->and(app(LevelService::class)->totalXp(null))->toBe(0);
});

test('level titles climb with the level', function () {
    $service = app(LevelService::class);

    // Enough XP to comfortably reach the "Focused" band (level >= 10).
    expect($service->breakdown(5000)['title'])->toBe('Focused');
});
