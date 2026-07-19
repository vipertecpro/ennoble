<?php

namespace Ennoble\Lottie\Console;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;

/**
 * Bundles the app's Lottie animations into the native project at build time.
 *
 * Drop `.json` / `.lottie` files into the app's `resources/animations/` and
 * they're copied where each platform's renderer looks for them:
 *   - iOS     → NativePHP/Resources/ (bundled; loaded via `.named(<basename>)`)
 *   - Android → app/src/main/assets/animations/ (loaded via the AssetManager as
 *               `animations/<file>`)
 *
 * The filename minus extension is the token referenced in Blade, e.g.
 * `resources/animations/water-fill.json` → `<native:lottie-player source="water-fill" />`.
 *
 * Wired as the plugin's `copy_assets` hook in nativephp.json; the build's
 * PluginHookRunner invokes it per platform during `native:run`.
 */
class CopyAnimationsCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:lottie:copy-animations';

    protected $description = 'Copy Lottie animations into the native project';

    /** Lottie container formats we bundle. */
    protected array $extensions = ['json', 'lottie'];

    public function handle(): int
    {
        $dir = base_path('resources/animations');

        if (! is_dir($dir)) {
            $this->info('No resources/animations/ directory found, skipping Lottie assets');

            return self::SUCCESS;
        }

        $files = glob($dir.'/*.{'.implode(',', $this->extensions).'}', GLOB_BRACE) ?: [];

        if (empty($files)) {
            $this->info('No Lottie animation files found in resources/animations/');

            return self::SUCCESS;
        }

        $this->info('Found '.count($files).' Lottie animation(s)');

        foreach ($files as $file) {
            if ($this->isIos()) {
                $this->copyFile($file, $this->buildPath().'/NativePHP/Resources/'.basename($file));
            }

            if ($this->isAndroid()) {
                $this->copyFile($file, $this->buildPath().'/app/src/main/assets/animations/'.basename($file));
            }
        }

        return self::SUCCESS;
    }
}
