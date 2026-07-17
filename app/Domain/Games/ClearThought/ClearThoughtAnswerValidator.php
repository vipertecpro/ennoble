<?php

namespace App\Domain\Games\ClearThought;

use App\Enums\ClearThoughtMode;
use App\Models\Challenge;

final class ClearThoughtAnswerValidator
{
    /**
     * Validate a response only against bundled accepted answers.
     *
     * @param  array<string, mixed>  $response
     */
    public function isCorrect(Challenge $challenge, array $response): bool
    {
        return match ($challenge->mode) {
            ClearThoughtMode::RemoveUnnecessaryWords => $this->matchesUnorderedSelection(
                $response['selected'] ?? [],
                $challenge->accepted_answers,
            ),
            ClearThoughtMode::ReorderSentence => $this->matchesOrderedSelection(
                $response['segments'] ?? [],
                $challenge->accepted_answers,
            ),
            ClearThoughtMode::ChooseClearestSentence => in_array(
                (string) ($response['option'] ?? ''),
                array_map(static fn (mixed $answer): string => (string) $answer, $challenge->accepted_answers),
                true,
            ),
        };
    }

    /**
     * @param  array<int, mixed>  $acceptedAnswers
     */
    private function matchesUnorderedSelection(mixed $selection, array $acceptedAnswers): bool
    {
        if (! is_array($selection)) {
            return false;
        }

        $normalizedSelection = $this->normalizeList($selection, sort: true);

        foreach ($acceptedAnswers as $acceptedAnswer) {
            if (is_array($acceptedAnswer) && $normalizedSelection === $this->normalizeList($acceptedAnswer, sort: true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $acceptedAnswers
     */
    private function matchesOrderedSelection(mixed $selection, array $acceptedAnswers): bool
    {
        if (! is_array($selection)) {
            return false;
        }

        $normalizedSelection = $this->normalizeList($selection);

        foreach ($acceptedAnswers as $acceptedAnswer) {
            if (is_array($acceptedAnswer) && $normalizedSelection === $this->normalizeList($acceptedAnswer)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function normalizeList(array $values, bool $sort = false): array
    {
        $normalized = array_map(static fn (mixed $value): string => (string) $value, array_values($values));

        if ($sort) {
            sort($normalized);
        }

        return $normalized;
    }
}
