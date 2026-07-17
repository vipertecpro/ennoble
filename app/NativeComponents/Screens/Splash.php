<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Screens\PlaceholderScreen;
use Native\Mobile\Edge\Element;

class Splash extends PlaceholderScreen
{
    protected const TITLE = 'Ennoble';

    protected const SUBTITLE = 'Native application shell';

    public string $shellState = 'content';

    public function render(): Element
    {
        return $this->view('screens.splash');
    }
}
