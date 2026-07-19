<?php

namespace App\Domain\Games\QuickMath;

use App\Models\GameLevel;

/**
 * Deterministic, fully-offline arithmetic generator for Quick Math. Problems
 * are derived from a string seed (stable per session) plus the level's
 * configuration (allowed operations and operand ranges), so a session always
 * replays the same problem set without any bundled content.
 */
final class QuickMathGenerator
{
    private const OFFSETS = [-12, -8, -5, -3, -2, -1, 1, 2, 3, 5, 8, 12];

    /**
     * Build the session's problem set.
     *
     * @return list<array{expression: string, answer: int, options: list<int>}>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $config = is_array($level->configuration) ? $level->configuration : [];
        /** @var list<string> $operations */
        $operations = $config['operations'] ?? ['add', 'subtract', 'multiply'];
        $operands = $config['operand_range'] ?? ['min' => 2, 'max' => 12];
        $min = (int) ($operands['min'] ?? 2);
        $max = max($min + 1, (int) ($operands['max'] ?? 12));

        $problems = [];

        for ($index = 0; $index < $count; $index++) {
            $problems[] = $this->buildProblem(
                operations: $operations,
                min: $min,
                max: $max,
                seed: $seed.':problem:'.$index,
            );
        }

        return $problems;
    }

    /**
     * @param  list<string>  $operations
     * @return array{expression: string, answer: int, options: list<int>}
     */
    private function buildProblem(array $operations, int $min, int $max, string $seed): array
    {
        $operation = $operations[$this->intFromSeed($seed.':op', 0, count($operations) - 1)];
        $left = $this->intFromSeed($seed.':l', $min, $max);
        $right = $this->intFromSeed($seed.':r', $min, $max);

        [$expression, $answer] = match ($operation) {
            'subtract' => [
                max($left, $right).' − '.min($left, $right),
                abs($left - $right),
            ],
            'multiply' => [
                $left.' × '.$right,
                $left * $right,
            ],
            'divide' => [
                ($left * $right).' ÷ '.$left,
                $right,
            ],
            default => [
                $left.' + '.$right,
                $left + $right,
            ],
        };

        return [
            'expression' => $expression,
            'answer' => $answer,
            'options' => $this->options($answer, $seed.':opt'),
        ];
    }

    /**
     * Build four unique answer tiles (the correct answer plus three plausible
     * near-miss distractors), deterministically ordered by seed.
     *
     * @return list<int>
     */
    private function options(int $answer, string $seed): array
    {
        $values = [$answer];
        $offsets = $this->seededOrder(self::OFFSETS, $seed.':offsets');

        foreach ($offsets as $offset) {
            $candidate = $answer + $offset;

            if ($candidate < 0 || in_array($candidate, $values, true)) {
                continue;
            }

            $values[] = $candidate;

            if (count($values) === 4) {
                break;
            }
        }

        // Guarantee four tiles even for tiny answers where offsets collided.
        $filler = 1;
        while (count($values) < 4) {
            $candidate = $answer + $filler;

            if (! in_array($candidate, $values, true)) {
                $values[] = $candidate;
            }

            $filler++;
        }

        return $this->seededOrder($values, $seed.':order');
    }

    private function intFromSeed(string $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        $value = hexdec(substr(hash('sha1', $seed), 0, 12));

        return $min + (int) ($value % ($max - $min + 1));
    }

    /**
     * @template TValue
     *
     * @param  list<TValue>  $items
     * @return list<TValue>
     */
    private function seededOrder(array $items, string $seed): array
    {
        $keyed = [];

        foreach (array_values($items) as $index => $item) {
            $keyed[] = ['key' => hash('sha1', $seed.'#'.$index), 'value' => $item];
        }

        usort($keyed, fn (array $a, array $b): int => strcmp($a['key'], $b['key']));

        return array_map(fn (array $entry) => $entry['value'], $keyed);
    }
}
