<?php

/**
 * Manifest integrity.
 *
 * The manifest is the only thing binding the `<native:scene-3d>` tag to its
 * PHP element and to each platform's renderer — none of it is checked by the
 * PHP compiler, so a rename here fails silently at BUILD time on a device
 * rather than loudly in CI. These tests are the guard for that.
 */
beforeEach(function () {
    $this->pluginPath = dirname(__DIR__);
    $this->manifest = json_decode(
        file_get_contents($this->pluginPath.'/nativephp.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
});

it('declares the scene_3d element with a renderer on both platforms', function () {
    $components = $this->manifest['components'];

    expect($components)->toHaveCount(1);

    $scene = $components[0];

    expect($scene['type'])->toBe('scene_3d')
        ->and($scene['ios_renderer'])->not->toBeEmpty()
        ->and($scene['android_renderer'])->not->toBeEmpty()
        ->and($scene['self_closing'])->toBeTrue();
});

it('points at PHP classes that actually exist', function () {
    // The classic silent break: a class is renamed and the manifest is not.
    foreach ($this->manifest['components'] as $component) {
        expect(class_exists($component['element']))
            ->toBeTrue("Element [{$component['element']}] named in the manifest does not exist.");

        expect(class_exists($component['blade']))
            ->toBeTrue("Blade component [{$component['blade']}] named in the manifest does not exist.");
    }
});

it('ships no bridge functions, because the viewport is declarative', function () {
    // PHP describes the scene as a prop and taps come back through the
    // element's own callback. A bridge function here would mean someone had
    // started driving the scene imperatively, which the renderer cannot batch.
    expect($this->manifest['bridge_functions'])->toBe([])
        ->and($this->manifest['events'])->toBe([]);
});

it('declares Filament on both platforms so one asset format serves both', function () {
    $android = $this->manifest['android']['dependencies']['implementation'];

    expect(implode(' ', $android))
        ->toContain('filament-android')
        // gltfio is what loads glTF with precompiled ubershaders — without it
        // there is no model loading and no character animation.
        ->toContain('gltfio-android');

    expect($this->manifest['ios']['dependencies']['pods'])->toContain('Filament');
});

it('targets platform versions Filament actually supports', function () {
    expect($this->manifest['android']['min_version'])->toBeGreaterThanOrEqual(24)
        ->and((float) $this->manifest['ios']['min_version'])->toBeGreaterThanOrEqual(13.0);
});
