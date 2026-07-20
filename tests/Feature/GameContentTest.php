<?php

use App\Domain\Games\Content\GameContentRepository;

/**
 * Guards the bundled, contributor-editable game content so a malformed edit
 * fails CI instead of shipping. See CONTENT.md for the authoring rules.
 */
test('the word match content bank is well-formed', function () {
    GameContentRepository::flush();
    $bank = (new GameContentRepository)->load('word-match');

    expect($bank)->toHaveKeys(['beginner', 'intermediate', 'advanced']);

    foreach ($bank as $band => $entries) {
        expect($entries)->toBeArray()
            ->and(count($entries))->toBeGreaterThanOrEqual(8, "band '{$band}' needs at least 8 entries");

        $prompts = [];

        foreach ($entries as $entry) {
            expect($entry)->toHaveKeys(['prompt', 'relation', 'answer', 'distractors']);
            expect($entry['prompt'])->toBeString()->not->toBe('');
            expect($entry['answer'])->toBeString()->not->toBe('');
            expect($entry['relation'])->toBeIn(['synonym', 'antonym']);

            expect($entry['distractors'])->toBeArray()->toHaveCount(3);
            expect(count(array_unique($entry['distractors'])))->toBe(3, "duplicate distractor in '{$entry['prompt']}'");

            foreach ($entry['distractors'] as $distractor) {
                expect($distractor)->toBeString()->not->toBe('');
                expect(strtolower($distractor))->not->toBe(strtolower($entry['answer']));
            }

            $prompts[] = strtolower($entry['prompt']);
        }

        expect(count(array_unique($prompts)))->toBe(count($prompts), "duplicate prompt in band '{$band}'");
    }
});

test('missing content sets resolve to an empty array rather than throwing', function () {
    expect((new GameContentRepository)->load('does-not-exist'))->toBe([]);
});
