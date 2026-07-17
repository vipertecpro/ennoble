<?php

use App\Enums\GameType;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('content migration seeds only bundled definitions', function () {
    expect(Game::query()->count())->toBe(2)
        ->and(Game::query()->pluck('type')->all())->toContain(
            GameType::SignalShift,
            GameType::ClearThought,
        )
        ->and(DB::table('game_levels')->count())->toBe(6)
        ->and(DB::table('achievements')->count())->toBe(6)
        ->and(DB::table('profiles')->count())->toBe(0)
        ->and(DB::table('daily_workouts')->count())->toBe(0)
        ->and(DB::table('statistics')->count())->toBe(0);
});

test('bundled seeders are idempotent and preserve in-progress data', function () {
    $profile = Profile::factory()->create();
    $game = Game::query()->where('type', GameType::SignalShift)->firstOrFail();
    $level = $game->levels()->firstOrFail();
    $session = GameSession::factory()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
        'status' => SessionStatus::InProgress,
    ]);

    $this->seed(DatabaseSeeder::class);

    expect(Game::query()->count())->toBe(2)
        ->and(DB::table('game_levels')->count())->toBe(6)
        ->and(DB::table('achievements')->count())->toBe(6);

    $this->assertModelExists($session);
    expect($session->refresh()->status)->toBe(SessionStatus::InProgress);
});

test('product migrations upgrade the previous scaffold schema without losing legacy rows', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'ennoble-upgrade-');
    $originalConnection = DB::getDefaultConnection();
    $baselineMigrations = [
        database_path('migrations/0001_01_01_000000_create_users_table.php'),
        database_path('migrations/0001_01_01_000001_create_cache_table.php'),
        database_path('migrations/0001_01_01_000002_create_jobs_table.php'),
    ];
    $productMigrations = collect(glob(database_path('migrations/2026_*.php')))
        ->sort()
        ->values()
        ->all();

    config([
        'database.connections.upgrade_test' => [
            ...config('database.connections.sqlite'),
            'database' => $databasePath,
            'foreign_key_constraints' => true,
        ],
    ]);
    DB::purge('upgrade_test');
    DB::setDefaultConnection('upgrade_test');

    try {
        Artisan::call('migrate', [
            '--database' => 'upgrade_test',
            '--path' => $baselineMigrations,
            '--realpath' => true,
            '--force' => true,
        ]);
        DB::table('users')->insert([
            'name' => 'Legacy Local User',
            'email' => 'legacy@example.test',
            'password' => 'not-used',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('migrate', [
            '--database' => 'upgrade_test',
            '--path' => $productMigrations,
            '--realpath' => true,
            '--force' => true,
        ]);

        expect(DB::table('users')->where('email', 'legacy@example.test')->exists())->toBeTrue()
            ->and(DB::table('games')->count())->toBe(2)
            ->and(DB::table('game_levels')->count())->toBe(6)
            ->and(DB::table('achievements')->count())->toBe(6)
            ->and(Schema::connection('upgrade_test')->hasColumn('profiles', 'onboarding_completed_at'))->toBeTrue();
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge('upgrade_test');

        if (is_string($databasePath) && file_exists($databasePath)) {
            unlink($databasePath);
        }
    }
});
