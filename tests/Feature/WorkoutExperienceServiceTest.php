<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Workout\WorkoutExperienceService;
use App\Domain\Workout\WorkoutService;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Enums\SkillKey;
use App\Enums\WorkoutStatus;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\ProgressSnapshot;
use App\Models\Setting;
use App\Models\Statistic;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-18 10:30:00');

    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('workout experience maps scalable journey states and truthful transition coaching', function () {
    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $signalItem = $workout->items->firstOrFail();
    $clearThoughtItem = $workout->items->last();
    $signalSession = app(GameSessionService::class)->startForWorkoutItem($this->profile, $signalItem);

    $signalSession->update([
        'status' => SessionStatus::Completed,
        'score' => 640,
        'accuracy' => 87.5,
        'correct_count' => 7,
        'missed_count' => 1,
        'best_combo' => 4,
        'completed_at' => now(),
    ]);
    $signalItem->update([
        'status' => WorkoutStatus::Completed,
        'completed_at' => now(),
    ]);
    $service = app(WorkoutExperienceService::class);

    expect($service->journey($workout->refresh(), $clearThoughtItem->getKey()))->toBe([
        ['label' => 'Signal Shift', 'position' => 1, 'state' => 'completed'],
        ['label' => 'Clear Thought', 'position' => 2, 'state' => 'current'],
    ])->and($service->transitionSummary($signalItem->refresh()))->toMatchArray([
        'coaching' => 'Great focus.',
        'detail' => 'You found a strong rhythm and held it.',
        'performance' => '87.5% accuracy · 640 points · x4 focus chain',
        'next_prompt' => 'Next, shift into clarity.',
    ]);

    GameSession::factory()
        ->for($this->profile)
        ->for($clearThoughtItem->game)
        ->for($clearThoughtItem->level, 'level')
        ->for($clearThoughtItem, 'workoutItem')
        ->completed()
        ->create([
            'score' => 480,
            'accuracy' => 80.0,
            'best_combo' => 0,
        ]);
    $clearThoughtItem->update([
        'status' => WorkoutStatus::Completed,
        'completed_at' => now(),
    ]);
    $clearSummary = $service->transitionSummary($clearThoughtItem->refresh());

    expect($clearSummary['coaching'])->toBe('Great focus.')
        ->and($clearSummary['performance'])->toBe('80% accuracy · 480 points')
        ->and($clearSummary['next_prompt'])->toBe('Your full daily rhythm is complete.');
});

test('workout completion surfaces only evidence-backed improvements moments streaks and achievements', function () {
    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $signalItem = $workout->items->firstOrFail();
    $clearThoughtItem = $workout->items->last();
    $signalSession = GameSession::factory()
        ->for($this->profile)
        ->for($signalItem->game)
        ->for($signalItem->level, 'level')
        ->for($signalItem, 'workoutItem')
        ->completed()
        ->create([
            'score' => 720,
            'accuracy' => 92.5,
            'best_combo' => 5,
        ]);
    GameSession::factory()
        ->for($this->profile)
        ->for($clearThoughtItem->game)
        ->for($clearThoughtItem->level, 'level')
        ->for($clearThoughtItem, 'workoutItem')
        ->completed()
        ->create([
            'score' => 300,
            'accuracy' => 70.0,
            'best_combo' => 0,
        ]);
    $clearThoughtItem->update([
        'status' => WorkoutStatus::Completed,
        'completed_at' => now(),
    ]);
    $signalItem->update([
        'status' => WorkoutStatus::Completed,
        'completed_at' => now(),
    ]);
    ProgressSnapshot::factory()->for($this->profile)->for($signalSession)->create([
        'skill_key' => SkillKey::Focus,
        'score_before' => 500,
        'score_after' => 507,
        'delta' => 7,
    ]);
    Statistic::factory()->for($this->profile)->create([
        'current_streak' => 3,
    ]);
    $workout->update([
        'status' => WorkoutStatus::Completed,
        'summary' => [
            'accuracy' => 92.5,
            'has_gameplay_evidence' => true,
        ],
    ]);
    $achievement = Achievement::query()->where('slug', 'first-step')->firstOrFail();
    AchievementUnlock::factory()
        ->for($this->profile)
        ->for($achievement)
        ->for($workout, 'dailyWorkout')
        ->create();

    $summary = app(WorkoutExperienceService::class)->completionSummary($workout->refresh());

    expect($summary)->toMatchArray([
        'achievement_title' => 'First Step',
        'best_moment_title' => 'First benchmark set',
        'coaching' => 'Excellent control today.',
        'skill_improvements' => [
            ['skill' => 'Focus', 'change' => '+7'],
        ],
        'streak' => 3,
        'streak_message' => '3 focused days in rhythm.',
    ]);
});
