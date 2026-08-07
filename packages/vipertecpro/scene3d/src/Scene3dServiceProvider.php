<?php

namespace Vipertecpro\Scene3d;

use Illuminate\Support\ServiceProvider;
use Vipertecpro\Scene3d\Commands\CopyAssetsCommand;

/**
 * The plugin has no runtime services to bind.
 *
 * `<native:scene-3d>` is a declarative EDGE element: the tag, its PHP element
 * and both platform renderers are wired through the `components` block in
 * nativephp.json, not through the container. The only thing registered here is
 * the build-time hook that copies 3D assets into the native projects.
 */
class Scene3dServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyAssetsCommand::class,
            ]);
        }
    }
}
