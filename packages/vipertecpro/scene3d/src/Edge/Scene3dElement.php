<?php

namespace Vipertecpro\Scene3d\Edge;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

/**
 * A real 3D viewport, rendered by SceneKit on iOS and SceneView on Android.
 *
 * WHY THE SCENE TRAVELS AS JSON. The EDGE wire props expose only scalars and
 * string lists — there is no object or float-array getter on the native side —
 * so a scene graph cannot be modelled as individual props. It ships as one
 * `scene` string that the renderer parses, which also keeps the whole scene
 * atomic: a frame never renders half-updated.
 *
 * WHY PHP SENDS STATE, NOT FRAMES. Exactly as with EDGE's transform tweens,
 * PHP describes where things ARE and where they are GOING, and the native
 * renderer interpolates at its own framerate. A node carrying `spin` or
 * `tween` keeps moving with no further contact from PHP, so the game's 250ms
 * logic tick never gates the animation. Re-sending an identical scene is a
 * no-op: the renderer diffs by node id and leaves untouched nodes — and their
 * running animations — alone.
 */
class Scene3dElement extends Element
{
    protected string $type = 'scene_3d';

    protected array $sceneProps = [];

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        // `scene` accepts an array (encoded here) or a pre-encoded string, so
        // Blade can pass a PHP structure without every caller remembering to
        // json_encode it.
        if (isset($attrs['scene'])) {
            $this->scene($attrs['scene']);
        }

        foreach (['background', 'camera-distance', 'camera-fov'] as $key) {
            if (isset($attrs[$key])) {
                $this->sceneProps[str_replace('-', '_', $key)] = $attrs[$key];
            }
        }

        if (isset($attrs['_nodeTap'])) {
            $this->onNodeTap($attrs['_nodeTap']);
        }
    }

    /**
     * @param  array<string, mixed>|string  $scene
     */
    public function scene(array|string $scene): static
    {
        $this->sceneProps['scene'] = is_string($scene)
            ? $scene
            : json_encode($scene, JSON_THROW_ON_ERROR);

        return $this;
    }

    /**
     * Method called when a node in the scene is tapped. Receives the node's
     * id as a string — the same shape as `@swipe` receiving a direction.
     */
    public function onNodeTap(string $method): static
    {
        $this->nodeTapMethod = $method;

        return $this;
    }

    protected ?string $nodeTapMethod = null;

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $props = $this->sceneProps;

        if ($this->nodeTapMethod !== null) {
            $props['on_node_tap'] = $registry->register($this->nodeTapMethod);
        }

        return $props;
    }
}
