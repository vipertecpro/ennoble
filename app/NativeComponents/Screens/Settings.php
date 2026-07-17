<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Screens\PlaceholderScreen;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;

class Settings extends PlaceholderScreen
{
    protected const TITLE = 'Settings';

    public function render(): Element
    {
        return $this->view('screens.settings');
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    protected function showsBackButton(): bool
    {
        return true;
    }
}
