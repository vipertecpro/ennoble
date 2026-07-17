<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Screens\PlaceholderScreen;
use Native\Mobile\Edge\Element;

class Profile extends PlaceholderScreen
{
    protected const TITLE = 'Profile';

    public function render(): Element
    {
        return $this->view('screens.profile');
    }
}
