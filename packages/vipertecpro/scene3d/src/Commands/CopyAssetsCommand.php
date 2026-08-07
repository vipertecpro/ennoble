<?php

namespace Vipertecpro\Scene3d\Commands;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;

/**
 * Copies the built-in primitive meshes into the native projects at build time.
 *
 * Wired as the plugin's `copy_assets` hook in nativephp.json; the build's
 * PluginHookRunner invokes it once per platform during `native:run`. Without
 * that hook entry this command never runs, and a scene renders as an empty
 * viewport with no error anywhere — the meshes are simply not on the device.
 *
 * Both platforms receive the SAME files under the same relative path
 * (`primitives/<shape>.glb`), which is the whole point of choosing glTF: the
 * renderers resolve a shape identically instead of each carrying its own asset
 * conventions. It matches `SceneNode.assetPath` in the Kotlin.
 *
 * A user's own models are NOT handled here — those live in the app and are
 * referenced by the path given to `Node::model()`.
 */
class CopyAssetsCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:scene3d:copy-assets';

    protected $description = 'Copy scene3d primitive meshes into the native projects';

    public function handle(): int
    {
        // iOS builds its primitives from SceneKit's own geometry, so there is
        // nothing to copy there. Returning success rather than failing keeps
        // the hook honest: it did everything that needed doing.
        if ($this->isIos()) {
            $this->info('scene3d: iOS uses built-in geometry, no meshes to copy.');

            return self::SUCCESS;
        }

        $source = $this->pluginPath().'/resources/primitives';

        if (! is_dir($source)) {
            $this->error("Primitives are missing from [{$source}]. Run nativephp:scene3d:primitives.");

            return self::FAILURE;
        }

        $meshes = glob($source.'/*.glb') ?: [];

        if ($meshes === []) {
            $this->error('No primitive meshes found. Run nativephp:scene3d:primitives.');

            return self::FAILURE;
        }

        $copied = 0;
        $failed = [];

        foreach ($meshes as $mesh) {
            $name = basename($mesh);

            // Paths are relative to the plugin's resources/ directory — the
            // helpers prepend pluginPath() themselves.
            $ok = $this->copyToAndroidAssets("primitives/{$name}", "primitives/{$name}");

            $ok ? $copied++ : $failed[] = $name;
        }

        // Report what actually happened. A copy hook that claims success while
        // copying nothing turns a build error into a blank viewport at runtime.
        if ($failed !== []) {
            $this->error('Failed to copy: '.implode(', ', $failed));

            return self::FAILURE;
        }

        $this->info("Copied {$copied} scene3d primitive(s) for {$this->platform()}.");

        return self::SUCCESS;
    }
}
