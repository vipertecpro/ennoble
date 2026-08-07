<?php

namespace Vipertecpro\Scene3d\Commands;

use Illuminate\Console\Command;
use Vipertecpro\Scene3d\Primitives\GltfWriter;
use Vipertecpro\Scene3d\Primitives\PrimitiveFactory;
use Vipertecpro\Scene3d\Scene\Shapes;

/**
 * Regenerates the built-in primitive meshes as glTF.
 *
 * The output is COMMITTED, not built on install: these files change only when
 * the generator changes, and shipping them means a consumer needs neither this
 * command nor a build step to get a lit cube on screen.
 */
class GeneratePrimitivesCommand extends Command
{
    protected $signature = 'nativephp:scene3d:primitives {--path= : Where to write the .glb files}';

    protected $description = 'Regenerate the built-in scene3d primitive meshes as binary glTF';

    public function handle(PrimitiveFactory $factory, GltfWriter $writer): int
    {
        $path = $this->option('path') ?: dirname(__DIR__, 2).'/resources/primitives';

        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            $this->error("Could not create [{$path}].");

            return self::FAILURE;
        }

        foreach (Shapes::ALL as $shape) {
            $glb = $writer->toGlb($factory->make($shape), $shape);
            file_put_contents("{$path}/{$shape}.glb", $glb);

            $this->line(sprintf('  %-10s %6.1f KB', $shape, strlen($glb) / 1024));
        }

        $this->info(count(Shapes::ALL).' primitives written to '.$path);

        return self::SUCCESS;
    }
}
