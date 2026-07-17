<?php

namespace App\NativeUI\Navigation;

use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\NativeComponent;

final class TopNavigation
{
    /**
     * Build the reusable native top bar for a screen.
     */
    public static function forScreen(
        NativeComponent $screen,
        ?string $backgroundColor = null,
        ?string $textColor = null,
    ): NavBar {
        $navigation = NavBar::make()
            ->title($screen->navTitle())
            ->displayMode('inline')
            ->scrollBehavior('pinned');

        if ($backgroundColor !== null) {
            $navigation->backgroundColor($backgroundColor);
        }

        if ($textColor !== null) {
            $navigation->textColor($textColor);
        }

        return $navigation;
    }
}
