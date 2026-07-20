<?php

namespace App\Domain\Games\WordMatch;

use App\Domain\Games\Content\GameContentRepository;
use App\Enums\Difficulty;

/**
 * Curated offline vocabulary bank for Word Match. The entries live in the
 * editable data file resources/game-content/word-match.php (see CONTENT.md) and
 * are read through {@see GameContentRepository}, so contributors can add or
 * remove words without touching this class. The selected entry is persisted per
 * round in `GameRound.response`.
 *
 * Each entry pairs a prompt word with one relation ("synonym" | "antonym"), the
 * single correct option, and three unambiguous distractors.
 */
final class WordBank
{
    public function __construct(private readonly GameContentRepository $content) {}

    /**
     * Return the vocabulary entries for a difficulty band. Adaptive resolves to
     * the intermediate band, matching the level selected for the profile.
     *
     * @return list<array{prompt: string, relation: string, answer: string, distractors: list<string>}>
     */
    public function forDifficulty(Difficulty $difficulty): array
    {
        $entries = $this->content->load('word-match');

        return $entries[$this->bucket($difficulty)] ?? [];
    }

    private function bucket(Difficulty $difficulty): string
    {
        return match ($difficulty) {
            Difficulty::Beginner => 'beginner',
            Difficulty::Advanced => 'advanced',
            default => 'intermediate',
        };
    }
}
