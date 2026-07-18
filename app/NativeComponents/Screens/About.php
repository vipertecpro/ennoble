<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Theme\ThemeManager;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;

final class About extends NativeComponent
{
    public string $versionLabel = '';

    /**
     * Apply the saved theme and resolve the bundled version label.
     */
    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();

        $version = config('nativephp.version');
        $this->versionLabel = is_string($version) && $version !== ''
            ? 'Version '.$version
            : 'Development build';
    }

    public function render(): Element
    {
        return $this->view('screens.about');
    }

    /**
     * Supply the About title to native chrome.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('About')
            ->subtitle('A private daily practice')
            ->back(true);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    /**
     * Return to the previous native screen.
     */
    public function goBack(): void
    {
        $this->back();
    }
}
