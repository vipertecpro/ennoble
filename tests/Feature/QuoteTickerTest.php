<?php

use App\NativeComponents\QuoteTicker;
use App\NativeUI\Home\GamingQuotes;
use Native\Mobile\Testing\Native;

/**
 * Flatten a rendered EDGE tree so a node can be located by its props.
 *
 * @param  array<string, mixed>  $node
 * @return list<array<string, mixed>>
 */
function tickerFlatten(array $node): array
{
    $nodes = [$node];

    foreach ($node['children'] ?? [] as $child) {
        $nodes = [...$nodes, ...tickerFlatten($child)];
    }

    return $nodes;
}

/**
 * The looping row — the inner strip that carries the infinite animation.
 *
 * @return array<string, mixed>|null
 */
function tickerLoop(array $tree): ?array
{
    return collect(tickerFlatten($tree))
        ->first(fn (array $node): bool => $node['type'] === 'row'
            && array_key_exists('animate-duration', $node['props'] ?? []));
}

/**
 * The outer row — the static offset that lines a resumed segment up with a
 * loop that can only restart from zero.
 *
 * @return array<string, mixed>|null
 */
function tickerBase(array $tree): ?array
{
    return collect(tickerFlatten($tree))
        ->first(fn (array $node): bool => $node['type'] === 'row'
            && array_key_exists('translate-x', $node['props'] ?? [])
            && ! array_key_exists('animate-duration', $node['props'] ?? []));
}

test('the catalogue ships at least twenty quotes, each attributed', function () {
    $quotes = GamingQuotes::all();

    expect(count($quotes))->toBeGreaterThanOrEqual(20);

    foreach ($quotes as $quote) {
        expect($quote['text'])->not->toBe('')
            ->and($quote['source'])->not->toBe('')
            ->and($quote['width'])->toBeGreaterThan(0);
    }

    // Every quote must be distinct, or the strip visibly repeats itself.
    expect(collect($quotes)->pluck('text')->unique())->toHaveCount(count($quotes));
});

test('the strip width is the exact sum of its slot widths', function () {
    // PHP cannot measure text, so it DICTATES each slot width and wraps on
    // their sum. If these two ever disagree the scroll drifts out of step.
    expect(GamingQuotes::stripWidth())
        ->toBe(array_sum(array_column(GamingQuotes::all(), 'width')));
});

test('the strip scrolls as one infinite native loop, not from PHP', function () {
    $tree = Native::test(QuoteTicker::class)->tree();
    $loop = tickerLoop($tree);

    // THE smoothness guard. Driving translate-x from a poll scrolled at the
    // right average speed but juddered, because Home re-renders every second on
    // its own clock and each render landed mid-tween. A declarative loop is
    // immune: identical props every frame means the diff never touches it.
    expect($loop)->not->toBeNull()
        ->and($loop['props']['animate-loop'])->toBeTrue()
        ->and($loop['props']['animate-easing'])->toBe('linear')
        // One full pass of the strip, so the wrap lands on a seam that repeats.
        ->and($loop['props']['translate-x'])->toBe((float) -GamingQuotes::stripWidth())
        ->and($loop['props']['animate-duration'])->toBeGreaterThan(10000.0);

    // Every quote is on the strip, plus the repeated opening slots.
    expect(count($loop['children'] ?? []))->toBe(GamingQuotes::count() + 3);
});

test('the ticker asks for no renders of its own', function () {
    // A `native:poll` here would re-render Home on the ticker's behalf for no
    // reason — the loop needs nothing from PHP once it is running.
    foreach (tickerFlatten(Native::test(QuoteTicker::class)->tree()) as $node) {
        expect($node['props'] ?? [])->not->toHaveKey('native-poll');
    }
});

test('the press-and-hold handlers are actually bound on the wire', function () {
    // `@tapDown`/`@tapUp` are aliases that the precompiler rewrites to
    // press-down/press-up callback ids. A typo here fails silently — the ticker
    // would simply never pause — so assert the ids reached the tree.
    $pressable = collect(tickerFlatten(Native::test(QuoteTicker::class)->tree()))
        ->first(fn (array $node): bool => $node['type'] === 'pressable');

    expect($pressable)->not->toBeNull()
        ->and($pressable['props']['on_press_down'] ?? 0)->not->toBe(0)
        ->and($pressable['props']['on_press_up'] ?? 0)->not->toBe(0);
});

test('holding stops the loop dead and releasing restarts it', function () {
    $ticker = Native::test(QuoteTicker::class);

    $ticker->call('hold')->assertSet('paused', true);

    $heldLoop = tickerLoop($ticker->tree());

    // Held: no animation at all, and the loop parked at identity so the static
    // outer offset alone decides what the eye sees.
    expect($heldLoop['props']['animate-duration'])->toBe(0.0)
        ->and($heldLoop['props']['animate-loop'])->toBeFalse()
        ->and($heldLoop['props']['translate-x'])->toBe(0.0);

    $ticker->call('release')->assertSet('paused', false);

    expect(tickerLoop($ticker->tree())['props']['animate-loop'])->toBeTrue();
});

test('a resumed segment rotates the strip so the restarting loop is seamless', function () {
    $ticker = Native::test(QuoteTicker::class);

    // A loop can only restart from zero, so resuming re-bases the segment: the
    // quote at the left edge is rotated to the front and its leftover fraction
    // moves onto the static outer row. Without both halves the strip jumps.
    $ticker->call('hold')->call('release');

    $base = tickerBase($ticker->tree());

    expect($base)->not->toBeNull()
        ->and($base['props']['translate-x'])->toBeLessThanOrEqual(0.0)
        ->and(abs($base['props']['translate-x']))->toBeLessThan(GamingQuotes::stripWidth())
        ->and($ticker->get('segmentIndex'))->toBeGreaterThanOrEqual(0)
        ->and($ticker->get('segmentIndex'))->toBeLessThan(GamingQuotes::count());
});

test('holding twice does not bank the elapsed time twice', function () {
    $ticker = Native::test(QuoteTicker::class);

    $ticker->call('hold');
    $banked = $ticker->get('accumulatedMs');

    $ticker->call('hold');

    expect($ticker->get('accumulatedMs'))->toBe($banked)
        ->and($ticker->get('paused'))->toBeTrue();
});
