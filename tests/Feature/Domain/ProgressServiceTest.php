<?php

use App\Domain\Progress\ProgressService;
use App\Enums\GameType;
use App\Enums\SkillKey;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\ProgressSnapshot;

test('progress service stores bounded current values and historical snapshots', function () {
    $profile = Profile::factory()->create();
    $service = app(ProgressService::class);

    $firstSnapshots = $service->updateSkillValues($profile, [
        SkillKey::Focus->value => 12,
        SkillKey::Speed->value => -8,
    ]);
    $service->updateSkillValues($profile, [
        SkillKey::Focus->value => 1000,
    ]);

    expect($firstSnapshots)->toHaveCount(2)
        ->and($firstSnapshots->first()->score_before)->toBe(500)
        ->and($firstSnapshots->first()->score_after)->toBe(512)
        ->and($service->currentSkillValues($profile))->toBe([
            SkillKey::Focus->value => 1000,
            SkillKey::Speed->value => 492,
        ])
        ->and($service->history($profile, SkillKey::Focus))->toHaveCount(2);
});

test('session-backed skill evidence is idempotent', function () {
    $profile = Profile::factory()->create();
    $game = Game::query()->where('type', GameType::SignalShift)->firstOrFail();
    $level = $game->levels()->firstOrFail();
    $session = GameSession::factory()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
    ]);
    $service = app(ProgressService::class);

    $service->updateSkillValues(
        $profile,
        [SkillKey::Precision->value => 5],
        $session,
    );
    $service->updateSkillValues(
        $profile,
        [SkillKey::Precision->value => 9],
        $session,
    );

    expect(ProgressSnapshot::query()
        ->whereBelongsTo($session, 'gameSession')
        ->forSkill(SkillKey::Precision)
        ->count())->toBe(1)
        ->and($service->currentSkillValues($profile)[SkillKey::Precision->value])->toBe(505);
});
