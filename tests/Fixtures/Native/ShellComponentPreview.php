<?php

namespace Tests\Fixtures\Native;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class ShellComponentPreview extends NativeComponent
{
    /**
     * Render the shared-component test fixture.
     */
    public function render(): View
    {
        return view('shell-component-preview');
    }

    /**
     * Receive fixture-only action callbacks.
     */
    public function noop(): void {}

    /**
     * Receive the reusable top bar's back callback.
     */
    public function goBack(): void
    {
        $this->back();
    }
}
