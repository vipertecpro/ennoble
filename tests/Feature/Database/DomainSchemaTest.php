<?php

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
        ->and(Schema::hasColumn('profiles', 'onboarding_completed_at'))->toBeTrue()
        ->and(Schema::hasColumns('game_sessions', [
            'state_snapshot',
            'current_round',
            'statistics_recorded_at',
            'completed_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumn('game_sessions', 'daily_workout_item_id'))->toBeFalse()
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
            'last_played_date',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('statistics', [
            'workouts_completed',
            'training_seconds',
            'last_workout_date',
        ]))->toBeFalse()
        ->and(Schema::hasColumn('achievements', 'tier'))->toBeTrue()
        ->and(Schema::hasColumn('achievement_unlocks', 'daily_workout_id'))->toBeFalse();
});

test('the daily workout tables are gone after the refactor', function () {
    expect(Schema::hasTable('daily_workouts'))->toBeFalse()
        ->and(Schema::hasTable('daily_workout_items'))->toBeFalse();
});

test('sqlite foreign keys and singleton uniqueness are enforced', function () {
    $foreignKeys = DB::selectOne('PRAGMA foreign_keys');

    expect((int) $foreignKeys->foreign_keys)->toBe(1);

    Profile::factory()->create();

    expect(fn () => Profile::factory()->create())
        ->toThrow(QueryException::class);
});
