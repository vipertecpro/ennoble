<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Screens\PlaceholderScreen;
use Native\Mobile\Edge\Element;

class Progress extends PlaceholderScreen
{
    protected const TITLE = 'Progress';

    public function render(): Element
    {
        return $this->view('screens.progress');
    }
}
