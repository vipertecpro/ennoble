<?php

namespace Ennoble\Lottie\Components;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

/**
 * Blade bridge for the Lottie element. Usable as the self-closing tag
 * <native:lottie-player source="…" />.
 */
class Lottie extends NativeBladeComponent
{
    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'lottie.player';
    }
}
