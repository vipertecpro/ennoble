<?php

use App\Models\DailyWorkout;
use App\Models\Profile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('fresh migrations create the complete offline domain schema', function () {
    $tables = [
        'profiles',
        'settings',
        'games',
        'game_levels',
        'challenges',
        'daily_workouts',
        'daily_workout_items',
        'game_sessions',
        'game_rounds',
        'progress_snapshots',
        'statistics',
        'achievements',
        'achievement_unlocks',
    ];

    expect(collect($tables)->every(
        fn (string $table): bool => Schema::hasTable($table),
    ))->toBeTrue()
        ->and(Schema::hasColumns('game_sessions', [
            'state_snapshot',
            'current_round',
            'statistics_recorded_at',
            'completed_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('settings', [
            'theme_preference',
            'sound_enabled',
            'haptics_enabled',
            'reduced_motion',
            'daily_reminder_enabled',
            'accessibility_preferences',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('statistics', [
            'accuracy',
            'average_response_ms',
            'best_score',
            'longest_combo',
            'current_streak',
            'longest_streak',
        ]))->toBeTrue();
});

test('sqlite foreign keys and singleton uniqueness are enforced', function () {
    $foreignKeys = DB::selectOne('PRAGMA foreign_keys');

    expect((int) $foreignKeys->foreign_keys)->toBe(1);

    Profile::factory()->create();

    expect(fn () => Profile::factory()->create())
        ->toThrow(QueryException::class);
});

test('one workout per profile and local date is enforced by the database', function () {
    $profile = Profile::factory()->create();

    DailyWorkout::factory()->for($profile)->create([
        'workout_date' => '2026-07-18',
    ]);

    expect(fn () => DailyWorkout::factory()->for($profile)->create([
        'workout_date' => '2026-07-18',
    ]))->toThrow(QueryException::class);
});
