<?php

namespace Vipertecpro\Scene3d\Components;

use Illuminate\View\Component;

/**
 * `<native:scene-3d>` — the Blade face of the 3D viewport.
 *
 * Declared in nativephp.json's `components` block, which is what binds this
 * tag to the PHP element and to the platform renderers; there is no separate
 * registration call.
 */
class Scene3d extends Component
{
    public function render(): string
    {
        return '';
    }
}
