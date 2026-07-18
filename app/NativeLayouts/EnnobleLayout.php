<?php

namespace App\NativeLayouts;

use App\Icons\AndroidOutlined;
use App\Icons\Ios;
use App\NativeComponents\Screens\Games;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\Profile;
use App\NativeComponents\Screens\Progress;
use App\NativeUI\Navigation\TopNavigation;
use App\NativeUI\Theme\ThemeManager;
use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\Builders\Tab;
use Native\Mobile\Edge\Layouts\Builders\TabBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

final class EnnobleLayout extends NativeLayout
{
    /**
     * Use the bounded EDGE chrome layout so scroll views receive a real viewport.
     */
    public function usesNativeChrome(): bool
    {
        return false;
    }

    /**
     * Build the shared title bar; screens may merge subtitle, back, and actions.
     */
    public function navBar(NativeComponent $screen): ?NavBar
    {
        if ($this->isPrimaryDestination($screen)) {
            return null;
        }

        $theme = app(ThemeManager::class);
        $preference = $theme->currentPreference();

        return TopNavigation::forScreen(
            screen: $screen,
            backgroundColor: $theme->color('background', $preference),
            textColor: $theme->color('on-background', $preference),
        );
    }

    /**
     * Build the four primary destinations with typed platform icons.
     */
    public function tabBar(NativeComponent $screen): ?TabBar
    {
        if (! $this->isPrimaryDestination($screen)) {
            return null;
        }

        $theme = app(ThemeManager::class);
        $preference = $theme->currentPreference();
        $isDark = $theme->appearance($preference) === 'dark';

        return TabBar::make()
            ->activeColor($theme->color('primary', $preference))
            ->textColor($theme->color('on-surface-variant', $preference))
            ->backgroundColor($theme->color('background', $preference))
            ->labelVisibility('labeled')
            ->dark($isDark)
            ->add(Tab::link('Home', '/', ios: Ios::House, android: AndroidOutlined::Home))
            ->add(Tab::link('Games', '/games', ios: Ios::Gamecontroller, android: AndroidOutlined::SportsEsports))
            ->add(Tab::link('Progress', '/progress', ios: Ios::ChartBar, android: AndroidOutlined::TrendingUp))
            ->add(Tab::link('Profile', '/profile', ios: Ios::Person, android: AndroidOutlined::Person));
    }

    private function isPrimaryDestination(NativeComponent $screen): bool
    {
        return $screen instanceof Home
            || $screen instanceof Games
            || $screen instanceof Progress
            || $screen instanceof Profile;
    }
}
