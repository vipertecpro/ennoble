# scene3d

Real-time 3D for NativePHP Mobile. One renderer, both platforms, described
entirely from PHP.

```php
$scene = Scene::make()
    ->background('#0B1020')
    ->camera((new Camera)->at(0, 1.5, 8)->lookAt(0, 0.5, 0))
    ->add(
        Node::model('hero', 'characters/hero.glb')
            ->at(0, 0, 0)
            ->play('walk'),
        Node::shape('orb', Shapes::SPHERE)
            ->at(2, 1, -1)
            ->material(Material::glowing('#10B981'))
            ->spin('y', 3.0)
            ->tappable(),
    );
```

```blade
<native:scene-3d class="flex-1 w-full rounded-3xl" :scene="$scene" @tap="strike" />
```

## Why Filament on both platforms

The obvious split — SceneKit on iOS, SceneView on Android — falls apart the
moment anyone brings a character: SceneKit has no glTF loader, so every model
would need per-platform conversion. For a plugin other people depend on, that
is a maintenance trap.

[Filament](https://github.com/google/filament) targets iOS, Android, Windows,
Linux, macOS and WebGL2, installs on iOS via CocoaPods, and its
[gltfio](https://github.com/google/filament/tree/main/libs/gltfio)
`UbershaderProvider` loads glTF with **precompiled materials — no `matc` at
runtime**. So one renderer, one asset format, identical behaviour, and skinned
characters come from the loader rather than being hand-built.

**Not Unity.** As a library it adds
[~110MB on iOS and ~90MB on Android](https://docs.unity3d.com/Manual/UnityasaLibrary.html),
holds 80–180MB even unloaded, renders **full-screen only** so it could never
sit beside a HUD, allows one runtime instance, and on iOS cannot be reloaded
once quit. This plugin adds a few MB and composes inside the normal element
tree. The trade is real: no editor, no physics, no asset pipeline.

## The contract

**PHP describes state, not frames.** The `#[Poll]` floor is ~250ms, so anything
stepped frame-by-frame from PHP will judder. A node given `spin()`, `moveTo()`
or `play()` keeps moving on the render thread with no further contact.

**Identity is the diff key.** Nodes are matched across frames by `id` and
updated in place, so a node keeps its GPU resources, skeleton and running
animations for as long as its id survives. Regenerating ids every frame forces a
rebuild and throws all of that away.

**Revisions make the diff cheap.** Every mutation bumps a node's `revision`, so
the renderer skips untouched nodes in O(1) rather than deep-comparing
descriptors. With a few hundred nodes that is the difference between a free
diff and a measurable one.

**Defaults are omitted from the wire.** A scene is re-encoded on every render
that touches it; bytes that are not there are the cheapest optimisation
available.

## Status

| Piece | State |
|---|---|
| Scene API (`Scene`, `Node`, `Material`, `Camera`, `Light`) | Done — 14 tests |
| EDGE element, Blade tag, manifest wiring | Done — 5 integrity tests |
| Built-in primitives (generated GLB) | Done — 13 tests, container validated |
| Android renderer (Filament + gltfio) | Renders on device — geometry, materials, spin |
| iOS renderer (Filament via CocoaPods) | **Not started** |
| Picking (`tappable()` → node id) | Modelled in PHP, **not wired natively** |
| Showcase games | Not started |

The PHP half is complete and tested. The Android renderer is written but has
never been through a Kotlin compiler — expect build fixes on first
`native:run`, particularly around Filament's lifecycle and the version pin.

The manifest declares **Android only**. iOS is deliberately undeclared until a
renderer exists — declaring it would add an unverified `pod 'Filament'` to real
iOS builds for a renderer that isn't there. A test enforces this: the declared
platforms must match the non-empty `resources/<platform>` directories.

### Verify before the first build

- Android coordinates are pinned to `1.51.6`; runtime and tools must come from
  the same Filament release.

### Before extracting to its own repository

- Drop `version` from `composer.json` — it exists only because a path
  repository has no tags; once the package has its own repo, tags supply it.
- Fill in the author email, homepage and repository in `nativephp.json`, and
  add the `icon` the marketplace shows.

## Roadmap

1. **Android renderer** — a `SurfaceView` host, the scene diff, primitives.
   Android first because Gradle coordinates are the lowest-risk integration.
2. **iOS renderer** — same diff against Filament's Metal backend.
3. **glTF + skinning** — `gltfio`, then animation clips.
4. **Picking** — tap → node id, already modelled by `tappable()`.
5. **Performance pass** — instancing for repeated geometry, frustum culling.
6. **Showcase games**, then extract to its own repository.

## Installing

```jsonc
"repositories": [{ "type": "path", "url": "packages/vipertecpro/scene3d" }]
```

```bash
composer require vipertecpro/scene3d
php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register vipertecpro/scene3d
php artisan native:plugin:list
```
