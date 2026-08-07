<?php

use Illuminate\Support\Facades\File;

/**
 * Motion rules that are invisible in PHP and only wrong on a device.
 *
 * Every one of these was a real defect: the app looked correct in the wire
 * tree and felt cheap in the hand.
 */
function motionViews(): array
{
    return collect(File::allFiles(resource_path('views')))
        ->filter(fn ($file): bool => str_ends_with($file->getFilename(), '.blade.php'))
        ->mapWithKeys(fn ($file): array => [
            str_replace(resource_path('views').'/', '', $file->getPathname()) => $file->getContents(),
        ])
        ->all();
}

test('no number is keyed by its own value', function () {
    // A `native:key` containing the value it displays changes whenever the
    // value does, so the renderer destroys the view and builds a new one —
    // and digits cannot roll between two different views. Keys name a ROLE.
    //
    // Keys built from a serial or a phase are fine and deliberate: those
    // exist precisely to re-mount and replay an entrance animation.
    $offenders = [];

    foreach (motionViews() as $path => $contents) {
        preg_match_all('/native:key="[^"]*\{\{\s*\$(score|combo|lines|level|bestScore|streak|xp)\b/', $contents, $matches);

        foreach ($matches[1] ?? [] as $variable) {
            $offenders[] = "{$path} keys on \${$variable}";
        }
    }

    expect($offenders)->toBe([]);
});

test('every rolling number declares the transition that rolls it', function () {
    // A stable key alone gets you a number that changes silently. The roll
    // itself is opt-in, and it is the effect people read as "polished".
    $missing = [];

    foreach (motionViews() as $path => $contents) {
        // Text nodes keyed as a score/level/lines role must ask for it.
        preg_match_all('/<native:text[^>]*native:key="[^"]*(score|level|lines)[^"]*"[^>]*>/s', $contents, $matches);

        foreach ($matches[0] ?? [] as $tag) {
            if (! str_contains($tag, 'content-transition')) {
                $missing[] = $path;
            }
        }
    }

    expect(array_unique($missing))->toBe([]);
});

test('reduced motion is honoured wherever a stagger is applied', function () {
    // A staggered entrance is motion, and someone who has asked for less of it
    // should get none — not a shorter version.
    $offenders = [];

    foreach (motionViews() as $path => $contents) {
        if (! str_contains($contents, ':animate-delay=')) {
            continue;
        }

        preg_match_all('/:animate-delay="([^"]*)"/', $contents, $matches);

        foreach ($matches[1] ?? [] as $expression) {
            // Either the view guards it, or it forwards a value a caller
            // guarded — a bare literal would ignore the preference.
            $guarded = str_contains($expression, 'reducedMotion') || preg_match('/^\$\w+$/', $expression);

            if (! $guarded) {
                $offenders[] = "{$path}: {$expression}";
            }
        }
    }

    expect($offenders)->toBe([]);
});
