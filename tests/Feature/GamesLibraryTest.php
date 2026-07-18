<?php

use App\Enums\Difficulty;
use App\Enums\GameType;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\Statistic;
use App\NativeComponents\Screens\WorkoutIntroduction;
use Carbon\CarbonImmutable;
use Database\Seeders\GameDefinitionSeeder;
use Database\Seeders\GameLevelSeeder;
use Native\Mobile\Edge\Transition;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-18 10:30:00');

    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create([
        'haptics_enabled' => true,
        'reduced_motion' => false,
    ]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('the games library presents one featured game and two playable games', function () {
    Native::visit('/games')
        ->assertElement('bottom_nav')
        ->assertSee('Train with purpose.')
        ->assertSee('Featured')
        ->assertSee('Signal Shift')
        ->assertSee('Start Training')
        ->assertSee('Available Games')
        ->assertSee('Clear Thought')
        ->assertSee('No best yet')
        ->assertSee('Not played yet')
        ->assertSee('No history yet')
        ->assertDontSee('Coming Soon')
        ->assertDontSee('This game is unavailable today')
        ->assertElement('row', fn (array $node): bool => ($node['ref'] ?? null) === 'game-filter-row-1')
        ->assertElement('row', fn (array $node): bool => ($node['ref'] ?? null) === 'game-filter-row-2')
        ->assertSet('featuredVisible', true)
        ->assertSet('selectedCategory', 'all')
        ->assertAccessible();
});

test('evidence-backed previews show best score completion count last played difficulty and completion rate', function () {
    $signalShift = Game::query()->where('type', GameType::SignalShift)->firstOrFail();
    $level = $signalShift->levels()
        ->where('difficulty', Difficulty::Intermediate)
        ->firstOrFail();

    GameSession::factory()->completed()->create([
        'profile_id' => $this->profile->getKey(),
        'game_id' => $signalShift->getKey(),
        'game_level_id' => $level->getKey(),
        'started_at' => now()->subDays(2),
        'completed_at' => now()->subDays(2)->addMinutes(4),
    ]);
    GameSession::factory()->completed()->create([
        'profile_id' => $this->profile->getKey(),
        'game_id' => $signalShift->getKey(),
        'game_level_id' => $level->getKey(),
        'started_at' => now()->subDay(),
        'completed_at' => now()->subDay()->addMinutes(4),
    ]);
    GameSession::factory()->create([
        'profile_id' => $this->profile->getKey(),
        'game_id' => $signalShift->getKey(),
        'game_level_id' => $level->getKey(),
        'started_at' => now(),
        'last_interaction_at' => now(),
    ]);
    Statistic::factory()
        ->for($this->profile)
        ->for($signalShift)
        ->create([
            'scope_key' => 'game:signal_shift',
            'sessions_completed' => 2,
            'best_score' => 1350,
        ]);

    $library = Native::visit('/games')
        ->assertSee('1350')
        ->assertSee('67%')
        ->assertSee('Today')
        ->assertSee('Intermediate')
        ->assertSee('Completed')
        ->assertSee('Play Again')
        ->assertElement('progress_bar', fn (array $node): bool => ($node['props']['value'] ?? null) === 0.67)
        ->assertAccessible();

    $signalShiftPreview = collect($library->get('playableGames'))
        ->firstWhere('slug', 'signal-shift');

    expect($signalShiftPreview['session_count'])->toBe(3)
        ->and($signalShiftPreview['completion_count'])->toBe(2);
});

test('category chips filter playable and featured games with subtle feedback', function () {
    $bridge = Native::fakeBridge()
        ->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games')
        ->toggle('filter-language', true)
        ->assertSet('selectedCategory', 'language')
        ->assertSet('featuredVisible', false)
        ->assertSee('Clear Thought')
        ->assertDontSee('Signal Shift')
        ->assertAccessible();

    expect($bridge->callsTo('Device.Vibrate'))->toHaveCount(1);
});

test('offline search matches titles and descriptions and presents an intentional empty state', function () {
    $library = Native::visit('/games')
        ->input('games-search', 'signal')
        ->assertSee('Signal Shift')
        ->assertDontSee('Clear Thought')
        ->assertAccessible();

    $library
        ->input('games-search', 'thought')
        ->assertSee('Clear Thought')
        ->assertDontSee('Signal Shift')
        ->assertAccessible();

    $library
        ->input('games-search', 'ocean')
        ->assertSee('No search results')
        ->assertSee('Try another title, category, or training focus.')
        ->assertAccessible();
});

test('a category with no current matches encourages exploration instead of leaving blank space', function () {
    Native::visit('/games')
        ->call('setCategory', 'memory')
        ->assertSee('No games found')
        ->assertSee('Show all games')
        ->assertAccessible();
});

test('play actions open the workout introduction without starting a session', function () {
    $bridge = Native::fakeBridge()
        ->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games')
        ->tap('Start Training')
        ->assertNavigatedTo('/workout')
        ->follow()
        ->assertScreen(WorkoutIntroduction::class)
        ->assertSee('Ready your mind.')
        ->assertSee('Begin Workout')
        ->assertMissingElement('bottom_nav')
        ->assertAccessible();

    expect(GameSession::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and($bridge->callsTo('Device.Vibrate'))->toHaveCount(1);
});

test('reduced motion removes authored game library transforms and navigation motion', function () {
    $this->profile->setting->update(['reduced_motion' => true]);

    Native::visit('/games')
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->assertSet('pressScale', 1.0)
        ->assertSet('pressOpacity', 1.0)
        ->tap('Start Training')
        ->assertNavigatedTo('/workout')
        ->assertTransition(Transition::None);
});

test('missing bundled definitions produce a recoverable library error', function () {
    Game::query()->where('type', GameType::ClearThought)->delete();

    $library = Native::visit('/games')
        ->assertSet('libraryState', 'error')
        ->assertSee('Your games library could not be loaded')
        ->assertAccessible();

    (new GameDefinitionSeeder)->run();
    (new GameLevelSeeder)->run();

    $library
        ->tap('Retry games library')
        ->assertSet('libraryState', 'content')
        ->assertSee('Signal Shift')
        ->assertSee('Clear Thought')
        ->assertAccessible();
});

test('loading and recoverable statistics states preserve the catalog', function () {
    Native::visit('/games')
        ->set('statisticsLoading', true)
        ->assertSee('Loading game statistics')
        ->assertSee('Signal Shift')
        ->assertAccessible()
        ->set('statisticsLoading', false)
        ->set('statisticsError', 'Local statistics are temporarily unavailable.')
        ->assertSee('Statistics unavailable')
        ->assertSee('Local statistics are temporarily unavailable.')
        ->assertSee('Clear Thought')
        ->assertAccessible();
});

test('an incomplete profile is returned to onboarding before the catalog loads', function () {
    $this->profile->update(['onboarding_completed_at' => null]);

    Native::visit('/games')
        ->assertReplacedWith('/onboarding');
});
