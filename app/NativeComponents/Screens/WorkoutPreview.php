<?php

namespace App\NativeComponents\Screens;

use App\NativeUI\Screens\PlaceholderScreen;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;

final class WorkoutPreview extends PlaceholderScreen
{
    protected const TITLE = 'Today’s Workout';

    protected const SUBTITLE = 'Training flow preview';

    public function render(): Element
    {
        return $this->view('screens.workout-preview');
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
