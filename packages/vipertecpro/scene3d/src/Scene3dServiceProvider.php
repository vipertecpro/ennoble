<?php

namespace Vipertecpro\Scene3d;

use Illuminate\Support\ServiceProvider;
use Vipertecpro\Scene3d\Commands\CopyAssetsCommand;

class Scene3dServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Scene3d::class, function () {
            return new Scene3d;
        });
    }

    public function boot(): void
    {
        // Register plugin hook commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyAssetsCommand::class,
            ]);
        }
    }
}
