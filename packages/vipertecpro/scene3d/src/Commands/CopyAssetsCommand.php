<?php

namespace Vipertecpro\Scene3d\Commands;

use Illuminate\Console\Command;

/**
 * Copies the built-in primitive meshes into the native projects at build time.
 *
 * Both platforms receive the SAME files under the same relative path
 * (`primitives/<shape>.gltf`), which is the whole point of choosing glTF: the
 * renderers can resolve a shape identically instead of each carrying its own
 * asset conventions.
 *
 * A user's own models are NOT handled here — those live in the app and are
 * referenced by the path given to `Node::model()`.
 */
class CopyAssetsCommand extends Command
{
    protected $signature = 'nativephp:scene3d:copy-assets';

    protected $description = 'Copy scene3d primitive meshes into the native projects';

    public function handle(): int
    {
        $source = dirname(__DIR__, 2).'/resources/primitives';

        if (! is_dir($source)) {
            $this->error("Primitives are missing from [{$source}]. Run nativephp:scene3d:primitives.");

            return self::FAILURE;
        }

        $copied = 0;

        foreach (glob($source.'/*.gltf') ?: [] as $file) {
            $name = basename($file);

            if (method_exists($this, 'copyToAndroidAssets')) {
                $this->copyToAndroidAssets($file, "primitives/{$name}");
            }

            if (method_exists($this, 'copyToIosBundle')) {
                $this->copyToIosBundle($file, "primitives/{$name}");
            }

            $copied++;
        }

        $this->info("Copied {$copied} scene3d primitives.");

        return self::SUCCESS;
    }
}
