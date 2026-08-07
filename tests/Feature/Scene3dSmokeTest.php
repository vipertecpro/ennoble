<?php

use App\NativeComponents\Screens\Scene3dSmoke;
use Native\Mobile\Testing\Native;

/**
 * Covers the scene3d smoke screen. Delete alongside it.
 *
 * These assertions are worth having even for a throwaway screen: they are the
 * only check that a scene reaches the wire at all, and they run in
 * milliseconds where the alternative is a full Android build and a look at a
 * device.
 */
function sceneNode(array $tree): ?array
{
    if (($tree['type'] ?? null) === 'scene_3d') {
        return $tree;
    }

    foreach ($tree['children'] ?? [] as $child) {
        if ($found = sceneNode($child)) {
            return $found;
        }
    }

    return null;
}

test('the smoke screen puts a scene on the wire', function () {
    $screen = Native::test(Scene3dSmoke::class);

    $node = sceneNode($screen->tree());

    expect($node)->not->toBeNull('No scene_3d element in the tree — the plugin tag did not resolve.');

    // The scene travels as one JSON string because EDGE props carry only
    // scalars; if this is not valid JSON the renderer silently draws nothing.
    $scene = json_decode($node['props']['scene'], true, flags: JSON_THROW_ON_ERROR);

    expect($scene['v'])->toBe(1)
        ->and($scene['bg'])->toBe('#FF00AA');
});

test('toggling the sphere adds a node without disturbing the box', function () {
    $screen = Native::test(Scene3dSmoke::class);

    $before = json_decode(sceneNode($screen->tree())['props']['scene'], true);

    $screen->call('toggleSphere');

    $after = json_decode(sceneNode($screen->tree())['props']['scene'], true);

    expect($before['n'])->toHaveCount(1)
        ->and($after['n'])->toHaveCount(2);

    // The whole diffing contract in one assertion: the untouched node must
    // arrive byte-identical, revision included, or the renderer rebuilds it
    // and its spin restarts.
    $box = fn (array $scene) => collect($scene['n'])->firstWhere('id', 'box');

    expect($box($after))->toBe($box($before));
});
