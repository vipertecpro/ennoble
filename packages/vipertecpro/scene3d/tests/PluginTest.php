<?php

use Vipertecpro\Scene3d\Edge\Scene3dElement;
use Vipertecpro\Scene3d\Scene\Shapes;

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

it('declares the scene_3d element with a renderer', function () {
    $components = $this->manifest['components'];

    expect($components)->toHaveCount(1);

    $scene = $components[0];

    expect($scene['type'])->toBe('scene_3d')
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

it('declares Filament and gltfio for Android', function () {
    $android = $this->manifest['android']['dependencies']['implementation'];

    expect(implode(' ', $android))
        ->toContain('filament-android')
        // gltfio is what loads glTF with precompiled ubershaders — without it
        // there is no model loading and no character animation.
        ->toContain('gltfio-android');

    expect($this->manifest['android']['min_version'])->toBeGreaterThanOrEqual(24);
});

it('declares only the platforms it can actually render', function () {
    // Declaring a platform pulls that platform's dependencies into a real
    // build. Claiming iOS while resources/ios is empty would add an
    // unverified `pod 'Filament'` and break an iOS build for a renderer that
    // is not there — so the manifest is checked against the sources rather
    // than against intent. Adding a renderer flips this test; the manifest
    // has to be updated in the same commit.
    foreach (['android', 'ios'] as $platform) {
        $sources = glob($this->pluginPath."/resources/{$platform}/*") ?: [];
        $declared = in_array($platform, $this->manifest['platforms'], strict: true);

        expect($declared)->toBe($sources !== [], $sources === []
            ? "Manifest declares [{$platform}] but resources/{$platform} is empty."
            : "resources/{$platform} has sources but the manifest omits [{$platform}].");

        // Renderer names are per-platform too, for the same reason.
        expect(isset($this->manifest['components'][0]["{$platform}_renderer"]))->toBe($declared);
    }
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

it('declares the copy_assets hook that actually bundles the meshes', function () {
    // This was the bug the first Android build shipped with: the manifest
    // carried an `assets` key, which nothing reads, and no `copy_assets` hook,
    // which is what the build's PluginHookRunner invokes. Everything compiled
    // and zero meshes reached the device — a blank viewport with no error.
    $hook = $this->manifest['hooks']['copy_assets'] ?? null;

    expect($hook)->not->toBeNull('No copy_assets hook: primitives will never be bundled.');

    // Asserted against the source rather than an instance: the command's base
    // class lives in nativephp/mobile, which the plugin's own suite does not
    // boot. The two facts that matter are both visible statically.
    $source = file_get_contents($this->pluginPath.'/src/Commands/CopyAssetsCommand.php');

    expect($source)->toContain("protected \$signature = '{$hook}'")
        // The base class is what supplies isAndroid()/copyToAndroidAssets();
        // extending Illuminate's Command instead makes every copy a silent no-op.
        ->and($source)->toContain('extends NativePluginHookCommand');
});

it('bundles the primitives the renderer resolves shapes to', function () {
    // SceneNode.assetPath falls back to "primitives/<shape>.glb"; a shape
    // without a bundled mesh renders as nothing at all, silently.
    foreach (Shapes::ALL as $shape) {
        expect(file_exists($this->pluginPath."/resources/primitives/{$shape}.glb"))
            ->toBeTrue("No bundled mesh for shape [{$shape}].");
    }
});

it('reads the tap handler off the key the compiler actually delivers', function () {
    // Hard-won: `@tap` is rewritten twice before it reaches an element, and a
    // custom `@nodeTap` is silently discarded entirely. Nothing errors when
    // this is wrong — the viewport simply never reports a tap — so it is
    // pinned here rather than rediscovered.
    $element = new Scene3dElement;
    $element->applyAttributes(['scene' => '{}', '_press' => 'strike']);

    $reflection = new ReflectionProperty($element, 'nodeTapMethod');

    expect($reflection->getValue($element))->toBe('strike');
});
