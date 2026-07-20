<?php

namespace App\Domain\Games\QuickMath;

/**
 * Builds a deterministic, fully-offline step-by-step explanation for a Quick
 * Math problem. The breakdown is derived purely from the displayed expression
 * and its answer — no bundled content, no network, no LLM — so it works the
 * same on-device as it does in tests.
 */
final class QuickMathExplainer
{
    private const OPERATORS = ['+', '−', '×', '÷'];

    /**
     * Produce the ordered assistant messages that explain how to reach the
     * answer for the given expression (e.g. "7 × 3").
     *
     * @return list<string>
     */
    public function explain(string $expression, int $answer): array
    {
        $parsed = $this->parse($expression);

        if ($parsed === null) {
            return ["The answer is {$answer}."];
        }

        [$left, $operator, $right] = $parsed;

        return match ($operator) {
            '+' => $this->explainAddition($left, $right, $answer),
            '−' => $this->explainSubtraction($left, $right, $answer),
            '×' => $this->explainMultiplication($left, $right, $answer),
            '÷' => $this->explainDivision($left, $right, $answer),
            default => ["The answer is {$answer}."],
        };
    }

    /**
     * @return list<string>
     */
    private function explainAddition(int $left, int $right, int $answer): array
    {
        return [
            "Let's add {$left} and {$right} together.",
            "Start at {$left}, then count up {$right} more.",
            "That lands you on {$answer}.",
            "So {$left} + {$right} = {$answer}.",
        ];
    }

    /**
     * @return list<string>
     */
    private function explainSubtraction(int $left, int $right, int $answer): array
    {
        return [
            "Let's take {$right} away from {$left}.",
            "Start at {$left} and count back {$right} steps.",
            "You're left with {$answer}.",
            "So {$left} − {$right} = {$answer}.",
        ];
    }

    /**
     * @return list<string>
     */
    private function explainMultiplication(int $left, int $right, int $answer): array
    {
        $steps = [
            "Let's multiply {$left} by {$right}.",
            "Multiplying is just adding {$left} to itself {$right} times.",
        ];

        // For small multipliers, show the repeated-addition chain so the
        // reasoning is concrete rather than a stated fact.
        if ($right >= 2 && $right <= 5) {
            $steps[] = implode(' + ', array_fill(0, $right, (string) $left))." = {$answer}.";
        }

        $steps[] = "So {$left} × {$right} = {$answer}.";

        return $steps;
    }

    /**
     * @return list<string>
     */
    private function explainDivision(int $left, int $right, int $answer): array
    {
        return [
            "Let's divide {$left} by {$right}.",
            "Ask: how many {$right}s fit into {$left}?",
            "{$right} × {$answer} = {$left}, so {$answer} of them fit.",
            "So {$left} ÷ {$right} = {$answer}.",
        ];
    }

    /**
     * Split an expression like "12 − 5" into its operands and operator.
     *
     * @return array{0: int, 1: string, 2: int}|null
     */
    private function parse(string $expression): ?array
    {
        $parts = preg_split('/\s+/', trim($expression)) ?: [];

        if (count($parts) !== 3) {
            return null;
        }

        [$left, $operator, $right] = $parts;

        if (! in_array($operator, self::OPERATORS, true) || ! is_numeric($left) || ! is_numeric($right)) {
            return null;
        }

        return [(int) $left, $operator, (int) $right];
    }
}
