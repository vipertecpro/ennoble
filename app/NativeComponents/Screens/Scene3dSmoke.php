<?php

namespace App\NativeComponents\Screens;

use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\Scene3d\Scene\Camera;
use Vipertecpro\Scene3d\Scene\Material;
use Vipertecpro\Scene3d\Scene\Node;
use Vipertecpro\Scene3d\Scene\Scene;
use Vipertecpro\Scene3d\Scene\Shapes;

/**
 * Development smoke test for the scene3d plugin. NOT a product screen —
 * delete this, its view, and its route once the renderer is trusted.
 *
 * It is built so that each way it can fail points at a different layer:
 *
 *   Nothing at all, or a crash    → FilamentHost: surface, engine, or teardown
 *   Flat magenta only             → engine and surface are fine, no mesh loaded
 *                                   (primitives missing from assets, or gltfio)
 *   A box that never moves        → geometry and materials work, frame loop dead
 *   A box that spins              → the whole path works
 *
 * The background is deliberately magenta rather than something tasteful: it is
 * a colour that appears nowhere else in the app, so seeing it is unambiguous
 * proof the viewport itself is drawing.
 */
final class Scene3dSmoke extends NativeComponent
{
    /** Toggles a second node, which is the only way to see the diff working. */
    public bool $showSphere = false;

    private function scene(): Scene
    {
        $scene = Scene::make()
            ->background('#FF00AA')
            ->camera((new Camera)->at(0, 1.5, 6)->lookAt(0, 0, 0))
            ->add(
                Node::shape('box', Shapes::BOX)
                    ->at(0, 0, 0)
                    ->material(Material::metal('#22D3EE'))
                    ->spin('y', 4.0),
            );

        if ($this->showSphere) {
            $scene = $scene->add(
                Node::shape('sphere', Shapes::SPHERE)
                    ->at(2.2, 0, 0)
                    ->scale(0.8)
                    ->material(Material::glowing('#FACC15')),
            );
        }

        return $scene;
    }

    /**
     * Adding a node must NOT restart the box's spin. If it stutters or resets
     * here, the renderer is rebuilding the whole scene instead of diffing it —
     * which is the single most important property to get right, because every
     * game re-sends the scene on every tick.
     */
    public function toggleSphere(): void
    {
        $this->showSphere = ! $this->showSphere;
    }

    public function render(): Element
    {
        return $this->view('screens.scene3d-smoke', [
            'scene' => $this->scene()->toArray(),
        ]);
    }
}
