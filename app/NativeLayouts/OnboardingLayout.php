<?php

namespace App\NativeLayouts;

use App\NativeUI\Navigation\TopNavigation;
use App\NativeUI\Theme\ThemeManager;
use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

final class OnboardingLayout extends NativeLayout
{
    /**
     * Keep onboarding in a native navigation host so iOS owns safe-area geometry.
     */
    public function usesNativeChrome(): bool
    {
        return true;
    }

    /**
     * Supply the native stack sentinel; the screen hides this bar visually.
     */
    public function navBar(NativeComponent $screen): ?NavBar
    {
        $theme = app(ThemeManager::class);
        $preference = $theme->currentPreference();

        return TopNavigation::forScreen(
            screen: $screen,
            backgroundColor: $theme->color('background', $preference),
            textColor: $theme->color('on-background', $preference),
        );
    }
}
