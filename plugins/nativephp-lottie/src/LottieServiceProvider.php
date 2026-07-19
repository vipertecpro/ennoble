<?php

namespace Ennoble\Lottie;

use Ennoble\Lottie\Console\CopyAnimationsCommand;
use Ennoble\Lottie\Elements\Lottie;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Edge\ElementRegistry;

class LottieServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Core plugin discovery registers the element under its manifest type
        // ("lottie.player"), but the <native:lottie-player> tag resolves to the
        // snake-cased "lottie_player". Alias that key to the same element so the
        // EDGE collector can build the node (its $type stays "lottie.player",
        // which the native renderer matches).
        ElementRegistry::register('lottie_player', Lottie::class);
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
