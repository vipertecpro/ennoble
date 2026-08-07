<?php

namespace Vipertecpro\Scene3d\Scene;

/**
 * The built-in primitives. Kept deliberately small: anything richer belongs in
 * a glTF model, where an artist can author it properly.
 */
final class Shapes
{
    public const BOX = 'box';

    public const SPHERE = 'sphere';

    public const CAPSULE = 'capsule';

    public const CYLINDER = 'cylinder';

    public const CONE = 'cone';

    public const TORUS = 'torus';

    public const PLANE = 'plane';

    public const ALL = [
        self::BOX, self::SPHERE, self::CAPSULE,
        self::CYLINDER, self::CONE, self::TORUS, self::PLANE,
    ];
}
