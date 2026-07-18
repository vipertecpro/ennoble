<?php

namespace App\Domain\Games\SignalShift;

use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class SignalShiftRule
{
    /**
     * Create one validated, data-driven Signal Shift rule.
     */
    public function __construct(
        public ?string $targetColor,
        public ?string $targetShape,
        public ?string $excludedShape,
        public ?bool $motionRequired,
        public ?string $sizeRequired,
        public ?bool $rotationRequired,
        public float $speedModifier,
        public int $spawnDensity,
        public int $waveCount,
        public int $secondsPerWave,
    ) {
        if ($this->targetShape !== null && $this->targetShape === $this->excludedShape) {
            throw new InvalidArgumentException('A Signal Shift target shape cannot also be excluded.');
        }

        if ($this->speedModifier < 0.5 || $this->speedModifier > 2.0) {
            throw new InvalidArgumentException('Signal Shift speed modifiers must be between 0.5 and 2.0.');
        }

        if ($this->spawnDensity < 2 || $this->spawnDensity > 6) {
            throw new InvalidArgumentException('Signal Shift spawn density must be between 2 and 6.');
        }

        if ($this->waveCount < 1 || $this->waveCount > 12) {
            throw new InvalidArgumentException('Signal Shift wave counts must be between 1 and 12.');
        }

        if ($this->secondsPerWave < 1 || $this->secondsPerWave > 10) {
            throw new InvalidArgumentException('Signal Shift wave timing must be between 1 and 10 seconds.');
        }
    }

    /**
     * Build a rule from bundled level configuration.
     *
     * @param  array<string, mixed>  $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            targetColor: self::nullableString($configuration['target_color'] ?? null),
            targetShape: self::nullableString($configuration['target_shape'] ?? null),
            excludedShape: self::nullableString($configuration['excluded_shape'] ?? null),
            motionRequired: self::nullableBoolean($configuration['motion_required'] ?? null),
            sizeRequired: self::nullableString($configuration['size_required'] ?? null),
            rotationRequired: self::nullableBoolean($configuration['rotation_required'] ?? null),
            speedModifier: (float) ($configuration['speed_modifier'] ?? 1.0),
            spawnDensity: (int) ($configuration['spawn_density'] ?? 4),
            waveCount: (int) ($configuration['wave_count'] ?? 2),
            secondsPerWave: (int) ($configuration['seconds_per_wave'] ?? 3),
        );
    }

    /**
     * Determine whether one generated stimulus satisfies every active condition.
     *
     * @param  array<string, mixed>  $stimulus
     */
    public function matches(array $stimulus): bool
    {
        if ($this->targetColor !== null && ($stimulus['color'] ?? null) !== $this->targetColor) {
            return false;
        }

        if ($this->targetShape !== null && ($stimulus['shape'] ?? null) !== $this->targetShape) {
            return false;
        }

        if ($this->excludedShape !== null && ($stimulus['shape'] ?? null) === $this->excludedShape) {
            return false;
        }

        if ($this->motionRequired !== null && ($stimulus['moving'] ?? null) !== $this->motionRequired) {
            return false;
        }

        if ($this->sizeRequired !== null && ($stimulus['size'] ?? null) !== $this->sizeRequired) {
            return false;
        }

        return $this->rotationRequired === null
            || ($stimulus['rotated'] ?? null) === $this->rotationRequired;
    }

    /**
     * Return concise player-facing rule copy.
     */
    public function instruction(): string
    {
        $subject = match (true) {
            $this->targetColor !== null && $this->targetShape !== null => $this->targetColor.' '.Str::plural($this->targetShape),
            $this->targetColor !== null => $this->targetColor.' shapes',
            $this->targetShape !== null => Str::plural($this->targetShape),
            default => 'every shape',
        };
        $conditions = [];

        if ($this->excludedShape !== null) {
            $conditions[] = 'except '.Str::plural($this->excludedShape);
        }

        if ($this->motionRequired !== null) {
            $conditions[] = $this->motionRequired ? 'that are moving' : 'that stay still';
        }

        if ($this->sizeRequired !== null) {
            $conditions[] = 'that are '.$this->sizeRequired;
        }

        if ($this->rotationRequired !== null) {
            $conditions[] = $this->rotationRequired ? 'that are tilted' : 'that are upright';
        }

        return Str::ucfirst(trim('Tap '.$subject.' '.implode(' ', $conditions))).'.';
    }

    /**
     * Serialize the rule for resumable local checkpoints.
     *
     * @return array<string, bool|float|int|string|null>
     */
    public function toArray(): array
    {
        return [
            'target_color' => $this->targetColor,
            'target_shape' => $this->targetShape,
            'excluded_shape' => $this->excludedShape,
            'motion_required' => $this->motionRequired,
            'size_required' => $this->sizeRequired,
            'rotation_required' => $this->rotationRequired,
            'speed_modifier' => $this->speedModifier,
            'spawn_density' => $this->spawnDensity,
            'wave_count' => $this->waveCount,
            'seconds_per_wave' => $this->secondsPerWave,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function nullableBoolean(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }
}
