<?php

namespace App\Domain\Games\SignalShift;

use App\Models\GameLevel;
use DomainException;
use Illuminate\Support\Arr;

final class SignalShiftRuleEngine
{
    /**
     * @return list<SignalShiftRule>
     */
    public function rulesFor(GameLevel $level): array
    {
        $configuredRounds = data_get($level->configuration, 'rounds');

        if (! is_array($configuredRounds) || count($configuredRounds) !== 3) {
            throw new DomainException('Signal Shift requires exactly three configured gameplay rounds.');
        }

        return array_map(
            static fn (array $configuration): SignalShiftRule => SignalShiftRule::fromArray($configuration),
            array_values($configuredRounds),
        );
    }

    public function ruleFor(GameLevel $level, int $roundNumber): SignalShiftRule
    {
        $rules = $this->rulesFor($level);

        if ($roundNumber < 1 || $roundNumber > count($rules)) {
            throw new DomainException('The requested Signal Shift round is not configured.');
        }

        return $rules[$roundNumber - 1];
    }

    public function tutorialRule(): SignalShiftRule
    {
        return SignalShiftRule::fromArray([
            'target_color' => 'teal',
            'target_shape' => 'circle',
            'speed_modifier' => 0.75,
            'spawn_density' => 4,
            'wave_count' => 1,
            'seconds_per_wave' => 8,
        ]);
    }

    /**
     * Generate one deterministic wave with exactly one eligible target.
     *
     * @param  array<string, mixed>  $levelConfiguration
     * @return list<array{
     *     id: string,
     *     color: string,
     *     shape: string,
     *     size: string,
     *     moving: bool,
     *     rotated: bool,
     *     direction: string,
     *     is_target: bool,
     *     label: string
     * }>
     */
    public function stimuliForWave(
        SignalShiftRule $rule,
        array $levelConfiguration,
        int $seed,
    ): array {
        $palette = $this->supportedValues(
            Arr::wrap($levelConfiguration['palette'] ?? []),
            ['teal', 'gold', 'coral'],
            $rule->targetColor,
        );
        $shapes = $this->supportedValues(
            Arr::wrap($levelConfiguration['shapes'] ?? []),
            ['circle', 'square', 'diamond'],
            $rule->targetShape,
            $rule->excludedShape,
        );
        $candidates = [];

        foreach ($palette as $color) {
            foreach ($shapes as $shape) {
                foreach (['small', 'large'] as $size) {
                    foreach ([false, true] as $moving) {
                        foreach ([false, true] as $rotated) {
                            $candidates[] = compact('color', 'shape', 'size', 'moving', 'rotated');
                        }
                    }
                }
            }
        }

        $targets = array_values(array_filter(
            $candidates,
            fn (array $candidate): bool => $rule->matches($candidate),
        ));
        $distractors = array_values(array_filter(
            $candidates,
            fn (array $candidate): bool => ! $rule->matches($candidate),
        ));

        if ($targets === [] || count($distractors) < $rule->spawnDensity - 1) {
            throw new DomainException('Signal Shift could not generate a balanced wave for this rule.');
        }

        $this->sortDeterministically($targets, $seed, 'target');
        $this->sortDeterministically($distractors, $seed, 'distractor');
        $stimuli = [$targets[0], ...array_slice($distractors, 0, $rule->spawnDensity - 1)];
        $this->sortDeterministically($stimuli, $seed, 'position');
        $directions = ['left', 'right', 'up', 'down'];

        return array_map(function (array $stimulus, int $index) use ($rule, $seed, $directions): array {
            $isTarget = $rule->matches($stimulus);
            $direction = $directions[abs(crc32($seed.'|direction|'.$index)) % count($directions)];
            $motionLabel = $stimulus['moving'] ? 'moving '.$direction : 'still';
            $rotationLabel = $stimulus['rotated'] ? 'tilted' : 'upright';

            return [
                ...$stimulus,
                'id' => 'signal-'.$seed.'-'.$index,
                'direction' => $direction,
                'is_target' => $isTarget,
                'label' => implode(', ', [
                    $stimulus['size'].' '.$stimulus['color'].' '.$stimulus['shape'],
                    $motionLabel,
                    $rotationLabel,
                ]),
            ];
        }, $stimuli, array_keys($stimuli));
    }

    /**
     * @param  list<mixed>  $configured
     * @param  list<string>  $defaults
     * @return list<string>
     */
    private function supportedValues(
        array $configured,
        array $defaults,
        ?string ...$required,
    ): array {
        $values = array_values(array_filter(
            $configured,
            static fn (mixed $value): bool => is_string($value) && in_array($value, $defaults, true),
        ));

        foreach ($required as $value) {
            if ($value !== null && in_array($value, $defaults, true)) {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values === [] ? $defaults : $values));
    }

    /**
     * @param  list<array<string, mixed>>  $values
     */
    private function sortDeterministically(array &$values, int $seed, string $scope): void
    {
        usort($values, static function (array $left, array $right) use ($seed, $scope): int {
            $leftHash = crc32($seed.'|'.$scope.'|'.json_encode($left, JSON_THROW_ON_ERROR));
            $rightHash = crc32($seed.'|'.$scope.'|'.json_encode($right, JSON_THROW_ON_ERROR));

            return $leftHash <=> $rightHash;
        });
    }
}
