<?php

namespace Vipertecpro\Scene3d\Components;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

/**
 * `<native:scene-3d>` — the Blade face of the 3D viewport.
 *
 * Self-closing: a scene's contents are described by the `scene` prop, not by
 * child tags. Nesting EDGE elements inside a 3D viewport would be meaningless —
 * they are 2D views and could only ever sit in front of it, which is what
 * placing them alongside in a `native:stack` already achieves.
 *
 * The binding from this tag to the PHP element and to each platform renderer
 * lives in nativephp.json's `components` block; there is no registration call.
 */
class Scene3d extends NativeBladeComponent
{
    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'scene_3d';
    }
}
