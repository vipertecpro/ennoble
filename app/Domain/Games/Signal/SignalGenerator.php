<?php

namespace App\Domain\Games\Signal;

use App\Models\GameLevel;

/**
 * Deterministic, fully-offline stimulus generator for Signal.
 *
 * Each round is a color name printed in a deliberately mismatched ink, plus the
 * rule in force for that round: `ink` (name the color you SEE) or `word` (name
 * the color you READ). The word and the ink are never the same, so every round
 * carries real interference, and the rule can flip between rounds — the
 * task-switching layer that makes Signal more than a plain Stroop drill.
 *
 * Everything is drawn from a stable seed, so a session always replays the same
 * run without any bundled content.
 */
final class SignalGenerator
{
    /** The rules a level may draw from. */
    private const RULES = ['ink', 'word'];

    /**
     * Build the session's stimulus set.
     *
     * @return list<array{rule: string, word: string, ink: string, answer: string, options: list<string>}>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $config = is_array($level->configuration) ? $level->configuration : [];
        $pool = SignalPalette::pool(is_array($config['colors'] ?? null) ? $config['colors'] : []);
        $rules = $this->rules($config);
        $optionsCount = max(2, min((int) ($config['options_count'] ?? 4), count($pool)));

        $rounds = [];
        $previousRule = null;
        $beforePreviousRule = null;

        for ($index = 0; $index < $count; $index++) {
            $rule = $this->pickRule($rules, $seed.':rule:'.$index, $previousRule, $beforePreviousRule);
            $word = $pool[$this->intFromSeed($seed.':word:'.$index, 0, count($pool) - 1)];
            $ink = $this->pickInk($pool, $seed.':ink:'.$index, $word);
            $answer = $rule === 'ink' ? $ink : $word;

            $rounds[] = [
                'rule' => $rule,
                'word' => $word,
                'ink' => $ink,
                'answer' => $answer,
                'options' => $this->options(
                    pool: $pool,
                    seed: $seed.':options:'.$index,
                    answer: $answer,
                    decoy: $rule === 'ink' ? $word : $ink,
                    optionsCount: $optionsCount,
                ),
            ];

            $beforePreviousRule = $previousRule;
            $previousRule = $rule;
        }

        return $rounds;
    }

    /**
     * The active, validated rule pool for this level.
     *
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private function rules(array $config): array
    {
        $configured = is_array($config['rules'] ?? null) ? $config['rules'] : [];
        $rules = array_values(array_unique(array_filter(
            $configured,
            static fn (mixed $rule): bool => in_array($rule, self::RULES, true),
        )));

        return $rules !== [] ? $rules : ['ink'];
    }

    /**
     * @param  list<string>  $rules
     */
    private function pickRule(array $rules, string $seed, ?string $previous, ?string $beforePrevious): string
    {
        $index = $this->intFromSeed($seed, 0, count($rules) - 1);
        $rule = $rules[$index];

        // Force a switch after two identical rules: an unbroken run of one rule
        // lets the player settle, which is exactly what Signal is testing for.
        if (count($rules) > 1 && $rule === $previous && $previous === $beforePrevious) {
            $rule = $rules[($index + 1) % count($rules)];
        }

        return $rule;
    }

    /**
     * Pick an ink that never matches the word — a congruent round has no
     * interference to resolve, so Signal never shows one.
     *
     * @param  list<string>  $pool
     */
    private function pickInk(array $pool, string $seed, string $word): string
    {
        $candidates = array_values(array_filter(
            $pool,
            static fn (string $color): bool => $color !== $word,
        ));

        if ($candidates === []) {
            return $word;
        }

        return $candidates[$this->intFromSeed($seed, 0, count($candidates) - 1)];
    }

    /**
     * Assemble the tap targets: the answer, the competing color it is being
     * confused with, then deterministic filler — shuffled so position carries
     * no information.
     *
     * @param  list<string>  $pool
     * @return list<string>
     */
    private function options(array $pool, string $seed, string $answer, string $decoy, int $optionsCount): array
    {
        $options = [$answer];

        if ($decoy !== $answer && count($options) < $optionsCount) {
            $options[] = $decoy;
        }

        $filler = array_values(array_filter(
            $pool,
            static fn (string $color): bool => ! in_array($color, $options, true),
        ));

        $offset = $filler === [] ? 0 : $this->intFromSeed($seed.':filler', 0, count($filler) - 1);

        for ($step = 0; $step < count($filler) && count($options) < $optionsCount; $step++) {
            $options[] = $filler[($offset + $step) % count($filler)];
        }

        return $this->shuffle($options, $seed.':shuffle');
    }

    /**
     * Seeded Fisher-Yates — the same seed always yields the same layout.
     *
     * @param  list<string>  $values
     * @return list<string>
     */
    private function shuffle(array $values, string $seed): array
    {
        for ($index = count($values) - 1; $index > 0; $index--) {
            $swap = $this->intFromSeed($seed.':'.$index, 0, $index);
            [$values[$index], $values[$swap]] = [$values[$swap], $values[$index]];
        }

        return array_values($values);
    }

    private function intFromSeed(string $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        $value = hexdec(substr(hash('sha1', $seed), 0, 12));

        return $min + (int) ($value % ($max - $min + 1));
    }
}
