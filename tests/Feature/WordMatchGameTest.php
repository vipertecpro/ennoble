<?php

use App\Domain\Games\GameSessionService;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\GameDetail;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\WordMatchGame;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->profile = Profile::factory()->onboarded()->create([
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create(['reduced_motion' => true]);
});

function startWordMatch(Profile $profile): GameSession
{
    $game = Game::query()->where('slug', 'word-match')->firstOrFail();
    $level = $game->levels()->where('difficulty', Difficulty::Intermediate)->firstOrFail();

    return app(GameSessionService::class)->startFreePlay($profile, $game, $level);
}

test('the game detail screen explains a game and launches a free-play session', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games/word-match')
        ->assertScreen(GameDetail::class)
        ->assertSee('Word Match')
        ->assertSee('How to play')
        ->assertAccessible()
        ->tap('Play')
        ->follow()
        ->assertScreen(WordMatchGame::class)
        ->assertSet('phase', 'ready');

    expect(GameSession::query()->count())->toBe(1);
});

test('a full correct Word Match playthrough records an evidence-backed score', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startWordMatch($this->profile);

    $screen = Native::visit('/play/word-match/'.$session->getKey())
        ->assertScreen(WordMatchGame::class)
        ->assertSet('phase', 'ready')
        ->call('tickGame')
        ->assertSet('phase', 'playing')
        ->assertAccessible();

    $totalRounds = $screen->get('totalRounds');

    for ($round = 0; $round < $totalRounds; $round++) {
        $answer = $screen->get('answer');
        $screen->call('chooseOption', $answer)->call('tickGame');
    }

    $screen->assertSet('phase', 'result')
        ->assertSet('resultCorrect', $totalRounds)
        ->assertAccessible();

    expect($screen->get('resultScore'))->toBeGreaterThan(0);

    $session->refresh();
    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->correct_count)->toBe($totalRounds)
        ->and($session->score)->toBeGreaterThan(0);
});

test('the simplified games library shows compact tiles and opens a detail', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/games')
        ->assertSee('Games')
        ->assertSee('Word Match')
        ->assertSee('Quick Math')
        ->assertDontSee('Train with purpose.')
        ->assertAccessible()
        ->tap('Word Match')
        ->assertNavigatedTo('/games/word-match');
});

test('the simplified home shows only progress and recent achievement', function () {
    Native::visit('/')
        ->assertScreen(Home::class)
        ->assertSee('Progress')
        ->assertSee('Recent Achievement')
        ->assertDontSee('TODAY’S SESSION')
        ->assertDontSee('Your rhythm')
        ->assertAccessible();
});

test('a wrong answer costs a life and a lost run ends the game', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    $session = startWordMatch($this->profile);

    $screen = Native::visit('/play/word-match/'.$session->getKey())
        ->call('tickGame')
        ->assertSet('phase', 'playing')
        ->assertSet('lives', 3);

    $wrong = collect($screen->get('options'))
        ->first(fn (string $option): bool => $option !== $screen->get('answer'));

    $screen->call('chooseOption', $wrong)
        ->assertSet('feedbackTone', 'wrong')
        ->assertSet('lives', 2)
        ->assertSet('combo', 0);
});
