<?php

namespace Ennoble\Lottie;

use Ennoble\Lottie\Console\CopyAnimationsCommand;
use Illuminate\Support\ServiceProvider;

class LottieServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyAnimationsCommand::class,
            ]);
        }
    }
}
