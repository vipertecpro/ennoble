# scene3d

A real 3D viewport for NativePHP Mobile, composable inside an EDGE tree.

```blade
<native:scene-3d
    class="flex-1 w-full rounded-3xl"
    background="#0B1020"
    camera-distance="7"
    :scene="$scene"
    @nodeTap="strike"
/>
```

## Why this instead of a game engine

Unity as a Library is the obvious answer and the wrong one here. It adds
[~110MB on iOS and ~90MB on Android](https://docs.unity3d.com/Manual/UnityasaLibrary.html),
holds 80–180MB of RAM even when unloaded, supports **full-screen rendering
only** — so it could never sit inside a screen next to a HUD — allows one
runtime instance, and on iOS cannot be reloaded once it has quit. For an app
whose games are a few minutes long, that is a lot of weight to carry.

SceneKit is part of the iOS SDK and SceneView wraps Filament on Android, so
this plugin adds a few MB, renders inside the normal element tree, and needs no
second toolchain. What it does **not** give you is an editor, a physics engine
or an asset pipeline — if you need those, revisit Unity.

## The contract

PHP describes **where things are and where they are going**. The renderer
interpolates at its own framerate. This is the same contract EDGE transforms
follow, and it exists for the same reason: PHP's poll floor is ~250ms, so
anything driven frame-by-frame from PHP will judder.

A node carrying `spin` or `tween` keeps moving with no further contact from
PHP. Re-sending an identical scene is a no-op — the renderer compares the raw
JSON first, then diffs by node id, so untouched nodes keep their running
animations. **Never rebuild the scene to change one node**; give the node an id
and change only that entry.

### Scene format

```php
$scene = [
    'nodes' => [
        [
            'id' => 'invader-3',        // stable identity — the diff key
            'shape' => 'box',           // box|sphere|capsule|cylinder|cone|torus|pyramid
            'color' => '#38BDF8',       // #RGB, #RRGGBB or #RRGGBBAA
            'x' => -1.2, 'y' => 0.4, 'z' => -3.0,
            'scale' => 1.0,
            'rx' => 0, 'ry' => 45, 'rz' => 0,   // degrees
            'opacity' => 1.0,
            'spin' => ['axis' => 'y', 'seconds' => 4],      // runs forever
            'tween' => ['to' => ['z' => 2.0], 'seconds' => 6], // one-shot
        ],
    ],
];
```

The scene travels as a single JSON string prop because the native props API
exposes only scalars and string lists — there is no object getter. That also
makes each update atomic, so a frame never renders half-applied.

### Tapping

`@nodeTap="method"` calls your method with the tapped node's **id** as a
string, exactly like `@swipe` gives you a direction. Ids beginning `__` are
reserved for viewport furniture (camera, lights) and are never reported.

## Status

| Piece | State |
|---|---|
| PHP element, Blade tag, manifest wiring | Written |
| iOS renderer (SceneKit) | Written, **not yet compiled** |
| Android renderer (SceneView) | **Not started** |

Neither native half has been through a compiler yet — there was no Swift or
Kotlin toolchain available when they were written. Expect a round of build
fixes on first `native:run`.

## Installing into the app

```jsonc
// composer.json
"repositories": [
    { "type": "path", "url": "packages/vipertecpro/scene3d" }
]
```

```bash
composer require vipertecpro/scene3d
php artisan vendor:publish --tag=nativephp-plugins-provider   # once, before the first plugin
php artisan native:plugin:register vipertecpro/scene3d
php artisan native:plugin:list                                 # verify
```

Then rebuild — native code only compiles in at build time.
