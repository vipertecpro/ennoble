<?php

namespace App\NativeComponents;

use App\NativeUI\Home\GamingQuotes;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;

/**
 * The gaming-quote ticker that runs across the top of Home. Press and hold to
 * stop it mid-sentence; release and it picks up exactly where it left off.
 *
 * WHY THIS DOES NOT ANIMATE FROM PHP. The obvious build — re-render on a poll
 * and hand the strip a fresh `translate-x` waypoint each tick — scrolls at
 * exactly the right average speed (measured 42.7pt/s against a 42pt/s target,
 * with no stalls) and still judders. The reason is that Home re-renders every
 * second on its OWN clock poll, and every one of those renders lands mid-tween
 * and re-applies the transform. No amount of waypoint tuning fixes that,
 * because the re-render is not the ticker's to control.
 *
 * So the scroll is ONE declarative `animate-loop` instead: a single infinite
 * native animation, set up once. Re-renders leave it alone entirely — the row's
 * key and props are identical every frame, so the diff never touches it and the
 * platform just keeps animating. PHP is out of the loop, which is the only way
 * to be immune to a neighbour's render cadence.
 *
 * WHAT THAT COSTS, AND HOW IT IS PAID. A looping animation always restarts from
 * identity, so it cannot be resumed part-way. The strip is therefore nested:
 *
 *   outer row — a STATIC translate of the sub-slot remainder
 *     inner row — the loop, always running 0 -> -stripWidth
 *
 * On resume, the quote list is rotated so the quote currently at the left edge
 * leads, and the leftover fraction of that quote goes on the outer row. The loop
 * then restarts from zero against content shifted to meet it, so the seam is
 * invisible. That rotation is computed ONCE per run segment and held in state —
 * recomputing it per render would change the props and restart the animation on
 * every one of Home's clock ticks, which is the exact bug this design avoids.
 *
 * Pause stays exact, because position remains a pure function of the wall
 * clock: holding banks the elapsed time and swaps the loop for a static row at
 * the offset the eye last saw.
 */
final class QuoteTicker extends NativeComponent
{
    /** Scroll speed in points per second — a readable amble, not a crawl. */
    private const SPEED = 42;

    /** How many opening quotes are repeated to cover the loop's seam. */
    private const SEAM_SLOTS = 3;

    public bool $paused = false;

    /** Running time banked from previous un-paused stretches. */
    public int $accumulatedMs = 0;

    /** When the current stretch started; null while held. */
    public ?int $runningSinceMs = null;

    /** Distance already travelled when the current run segment began. */
    public int $segmentBase = 0;

    /** Which quote leads the strip for this run segment. */
    public int $segmentIndex = 0;

    /** Static sub-slot offset that lines the rotated strip up with the loop. */
    public int $segmentRemainder = 0;

    public function mount(): void
    {
        $this->runningSinceMs = $this->nowMs();
        $this->beginSegment();
    }

    public function render(): Element
    {
        $stripWidth = max(1, GamingQuotes::stripWidth());

        // Held: draw the strip statically at the offset the eye last saw.
        // Running: hand the loop identical props every frame, so Home's clock
        // re-renders diff to nothing and the native animation is left alone.
        $progress = $this->paused
            ? ($this->travelled() - $this->segmentBase) % $stripWidth
            : 0;

        return $this->view('components.quote-ticker', [
            'slots' => $this->rotatedSlots(),
            'baseOffset' => -($this->segmentRemainder + $progress),
            'loopDistance' => -$stripWidth,
            'loopMs' => (int) round($stripWidth / self::SPEED * 1000),
            // Stable within a run segment so Home's clock re-renders leave the
            // animation alone; changes on resume so the loop restarts fresh.
            'segmentKey' => $this->segmentBase,
        ]);
    }

    /**
     * Hold to read. Banks the running time so the strip freezes exactly where
     * it is rather than snapping anywhere.
     */
    public function hold(): void
    {
        if ($this->paused) {
            return;
        }

        $this->accumulatedMs = $this->activeElapsedMs();
        $this->runningSinceMs = null;
        $this->paused = true;
    }

    /**
     * Release to resume. Re-bases the segment so the restarting loop picks up
     * against content rotated to meet it.
     */
    public function release(): void
    {
        if (! $this->paused) {
            return;
        }

        $this->runningSinceMs = $this->nowMs();
        $this->paused = false;
        $this->beginSegment();
    }

    /**
     * Pin the strip's rotation and sub-slot offset for a fresh run segment.
     */
    private function beginSegment(): void
    {
        $stripWidth = max(1, GamingQuotes::stripWidth());
        $travelled = $this->travelled();
        $offset = $travelled % $stripWidth;

        $this->segmentBase = $travelled;
        $this->segmentIndex = 0;
        $this->segmentRemainder = 0;

        foreach (GamingQuotes::all() as $index => $slot) {
            if ($offset < $slot['width']) {
                $this->segmentIndex = $index;
                $this->segmentRemainder = $offset;

                return;
            }

            $offset -= $slot['width'];
        }
    }

    /**
     * The quote list rotated so this segment's leading quote comes first, with
     * the opening quotes repeated to cover the loop's seam.
     *
     * @return list<array<string, mixed>>
     */
    private function rotatedSlots(): array
    {
        $quotes = GamingQuotes::all();
        $rotated = [
            ...array_slice($quotes, $this->segmentIndex),
            ...array_slice($quotes, 0, $this->segmentIndex),
        ];

        return [...$rotated, ...array_slice($rotated, 0, self::SEAM_SLOTS)];
    }

    private function travelled(): int
    {
        return (int) round($this->activeElapsedMs() / 1000 * self::SPEED);
    }

    private function activeElapsedMs(): int
    {
        if ($this->runningSinceMs === null) {
            return $this->accumulatedMs;
        }

        return $this->accumulatedMs + max(0, $this->nowMs() - $this->runningSinceMs);
    }

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
