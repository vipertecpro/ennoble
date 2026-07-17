<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Screens\PlaceholderScreen;
use Native\Mobile\Edge\Element;

class Home extends PlaceholderScreen
{
    protected const TITLE = 'Home';

    public function render(): Element
    {
        return $this->view('screens.home');
    }
}
