<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Screens\PlaceholderScreen;
use Native\Mobile\Edge\Element;

class Games extends PlaceholderScreen
{
    protected const TITLE = 'Games';

    public function render(): Element
    {
        return $this->view('screens.games');
    }
}
