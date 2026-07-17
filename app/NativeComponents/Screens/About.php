<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Screens\PlaceholderScreen;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;

class About extends PlaceholderScreen
{
    protected const TITLE = 'About';

    public function render(): Element
    {
        return $this->view('screens.about');
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
