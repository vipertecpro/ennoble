<?php

namespace App\NativeLayouts;

use App\Icons\AndroidOutlined;
use App\Icons\Ios;
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
     * Use the system-native NavigationStack/TabView and NavHost/Scaffold chrome.
     */
    public function usesNativeChrome(): bool
    {
        return true;
    }

    /**
     * Build the shared title bar; screens may merge subtitle, back, and actions.
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

    /**
     * Build the four primary destinations with typed platform icons.
     */
    public function tabBar(NativeComponent $screen): ?TabBar
    {
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
}
