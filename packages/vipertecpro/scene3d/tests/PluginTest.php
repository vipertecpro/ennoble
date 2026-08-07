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

it('has a Kotlin renderer whose package and object match the manifest', function () {
    // Nothing else checks this. The manifest names a fully-qualified Kotlin
    // object, and if the file is renamed or its package changes, the build
    // fails on a device with an unhelpful error rather than here.
    $declared = $this->manifest['components'][0]['android_renderer'];
    $object = substr($declared, strrpos($declared, '.') + 1);
    $package = substr($declared, 0, strrpos($declared, '.'));

    $file = $this->pluginPath."/resources/android/{$object}.kt";

    expect(file_exists($file))->toBeTrue("Manifest names [{$declared}] but {$object}.kt does not exist.");

    $source = file_get_contents($file);

    expect($source)->toContain("package {$package}")
        ->and($source)->toContain("object {$object}")
        // The renderer contract: a Composable Render(node, modifier).
        ->and($source)->toContain('fun Render(node: NativeUINode, modifier: Modifier)');
});

it('bundles the primitives the renderer resolves shapes to', function () {
    // SceneNode.assetPath falls back to "primitives/<shape>.gltf"; a shape
    // without a bundled mesh renders as nothing at all, silently.
    $declared = $this->manifest['assets']['android'];

    expect($declared)->toContain('resources/primitives');

    foreach (\Vipertecpro\Scene3d\Scene\Shapes::ALL as $shape) {
        expect(file_exists($this->pluginPath."/resources/primitives/{$shape}.gltf"))
            ->toBeTrue("No bundled mesh for shape [{$shape}].");
    }
});
