<?php

namespace Ennoble\Lottie\Elements;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

/**
 * Lottie EDGE element. Renders a bundled Lottie animation (dropped into the
 * app's resources/animations/ and copied into the native bundle at build time).
 *
 *   <native:lottie-player source="water-fill" :progress="$fraction" />   // driven timer
 *   <native:lottie-player source="success" loop autoplay />              // looping celebration
 *
 * `source` is the file's base name (without extension). When `progress`
 * (0.0–1.0) is provided the animation is frozen at that frame — ideal for
 * timers/progress; otherwise it plays (optionally looping).
 */
class Lottie extends Element
{
    protected string $type = 'lottie.player';

    /** @var array<string, mixed> */
    protected array $componentProps = [
        'loop' => false,
        'autoplay' => true,
        'speed' => 1.0,
    ];

    public static function make(string $source = ''): static
    {
        $element = new static;

        if ($source !== '') {
            $element->componentProps['source'] = $source;
        }

        return $element;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function applyAttributes(array $attrs): void
    {
        if (isset($attrs['source'])) {
            $this->componentProps['source'] = (string) $attrs['source'];
        }
        if (isset($attrs['loop'])) {
            $this->componentProps['loop'] = filter_var($attrs['loop'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($attrs['autoplay'])) {
            $this->componentProps['autoplay'] = filter_var($attrs['autoplay'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($attrs['speed'])) {
            $this->componentProps['speed'] = (float) $attrs['speed'];
        }
        if (isset($attrs['progress'])) {
            $this->componentProps['progress'] = max(0.0, min(1.0, (float) $attrs['progress']));
        }
        if (isset($attrs['alt'])) {
            $this->componentProps['alt'] = (string) $attrs['alt'];
        }

        $this->applyA11yAttributes($attrs);
    }

    public function source(string $source): static
    {
        $this->componentProps['source'] = $source;

        return $this;
    }

    public function loop(bool $loop = true): static
    {
        $this->componentProps['loop'] = $loop;

        return $this;
    }

    public function autoplay(bool $autoplay = true): static
    {
        $this->componentProps['autoplay'] = $autoplay;

        return $this;
    }

    public function speed(float $speed): static
    {
        $this->componentProps['speed'] = $speed;

        return $this;
    }

    public function progress(float $progress): static
    {
        $this->componentProps['progress'] = max(0.0, min(1.0, $progress));

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveProps(CallbackRegistry $registry): array
    {
        return $this->componentProps;
    }
}
