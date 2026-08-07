package com.vipertecpro.plugins.scene3d.ui

import android.content.Context
import android.view.Choreographer
import android.view.SurfaceView
import com.google.android.filament.Camera
import com.google.android.filament.Engine
import com.google.android.filament.EntityManager
import com.google.android.filament.LightManager
import com.google.android.filament.Renderer
import com.google.android.filament.Scene
import com.google.android.filament.Skybox
import com.google.android.filament.SwapChain
import com.google.android.filament.View
import com.google.android.filament.Viewport
import com.google.android.filament.android.DisplayHelper
import com.google.android.filament.android.UiHelper
import com.google.android.filament.gltfio.AssetLoader
import com.google.android.filament.gltfio.FilamentAsset
import com.google.android.filament.gltfio.ResourceLoader
import com.google.android.filament.gltfio.UbershaderProvider
import java.nio.ByteBuffer

/**
 * Owns one Filament engine, its surface, and the frame loop.
 *
 * WHY A HOST OBJECT AND NOT COMPOSE STATE. Filament resources are native
 * allocations with a strict destruction order — assets before the loader,
 * loader before the engine — and leaking an Engine leaks the GPU context for
 * the life of the process. Keeping all of it behind one object with one
 * `destroy()` means the teardown order is written once, here, instead of being
 * re-derived at every call site.
 *
 * The frame loop is a Choreographer callback rather than a thread: it is
 * already vsync-aligned, it pauses with the host window, and it keeps every
 * Filament call on the thread that created the Engine — Filament is not
 * thread-safe and cross-thread use fails as corruption rather than as an
 * exception.
 */
internal class FilamentHost(context: Context) {

    val surfaceView = SurfaceView(context)

    private val engine: Engine = Engine.create()
    private val renderer: Renderer = engine.createRenderer()
    private val scene: Scene = engine.createScene()
    private val view: View = engine.createView()
    private val cameraEntity = EntityManager.get().create()
    private val camera: Camera = engine.createCamera(cameraEntity)

    private val uiHelper = UiHelper(UiHelper.ContextErrorPolicy.DONT_CHECK)
    private val displayHelper = DisplayHelper(context)
    private val choreographer: Choreographer = Choreographer.getInstance()

    private var swapChain: SwapChain? = null

    private val materialProvider = UbershaderProvider(engine)
    private val assetLoader = AssetLoader(engine, materialProvider, EntityManager.get())
    private val resourceLoader = ResourceLoader(engine)

    private val graph = SceneGraph(engine, scene, assetLoader, resourceLoader, context)
    private val lightEntities = mutableListOf<Int>()

    private var environment: Environment.Built? = null
    private var chrome: SceneChrome? = null
    private var destroyed = false
    private var firstFrameNanos = 0L

    private val frameCallback = object : Choreographer.FrameCallback {
        override fun doFrame(frameTimeNanos: Long) {
            if (destroyed) return
            choreographer.postFrameCallback(this)

            if (firstFrameNanos == 0L) firstFrameNanos = frameTimeNanos
            val seconds = (frameTimeNanos - firstFrameNanos) / 1_000_000_000.0f

            // Spin and move are advanced here, on the render thread, so they
            // stay smooth regardless of how slowly PHP is ticking.
            graph.advance(seconds)

            val chain = swapChain ?: return
            if (renderer.beginFrame(chain, frameTimeNanos)) {
                renderer.render(view)
                renderer.endFrame()
            }
        }
    }

    init {
        view.scene = scene
        view.camera = camera
        // A skybox is what makes `background` actually clear to a colour;
        // without one the surface shows whatever was in the buffer before.
        scene.skybox = Skybox.Builder().color(0f, 0f, 0f, 1f).build(engine)

        uiHelper.renderCallback = object : UiHelper.RendererCallback {
            override fun onNativeWindowChanged(surface: android.view.Surface) {
                swapChain?.let { engine.destroySwapChain(it) }
                swapChain = engine.createSwapChain(surface)
                displayHelper.attach(renderer, surfaceView.display)
            }

            override fun onDetachedFromSurface() {
                displayHelper.detach()
                swapChain?.let {
                    engine.destroySwapChain(it)
                    // Flush before returning: the surface is about to go away
                    // and in-flight commands would reference dead memory.
                    engine.flushAndWait()
                    swapChain = null
                }
            }

            override fun onResized(width: Int, height: Int) {
                view.viewport = Viewport(0, 0, width, height)
                applyProjection(width, height)
            }
        }

        uiHelper.attachTo(surfaceView)
        choreographer.postFrameCallback(frameCallback)
    }

    /**
     * Apply a scene. Cheap when nothing changed: the graph compares node
     * revisions and leaves untouched entities — and their running animations —
     * completely alone.
     */
    fun apply(document: SceneDocument) {
        if (destroyed) return

        if (chrome != document.chrome) {
            applyChrome(document.chrome)
            chrome = document.chrome
        }

        graph.sync(document.nodes)
    }

    private fun applyChrome(next: SceneChrome) {
        val background = Transforms.linearColor(next.background ?: "#000000")
        scene.skybox?.let { engine.destroySkybox(it) }
        scene.skybox = Skybox.Builder()
            .color(background[0], background[1], background[2], 1f)
            .build(engine)

        // Metals are pure reflection: with no environment they render black,
        // which looks like a bug and is merely correct. The background is used
        // as the sky so reflections agree with what is actually behind the
        // object, with a darker ground half to keep the two distinguishable.
        environment?.let {
            scene.indirectLight = null
            engine.destroyIndirectLight(it.light)
            engine.destroyTexture(it.texture)
        }
        environment = Environment.build(
            engine,
            sky = background,
            ground = floatArrayOf(background[0] * 0.25f, background[1] * 0.25f, background[2] * 0.25f),
            intensity = 30_000f,
        ).also { scene.indirectLight = it.light }

        camera.lookAt(
            next.camX.toDouble(), next.camY.toDouble(), next.camZ.toDouble(),
            next.targetX.toDouble(), next.targetY.toDouble(), next.targetZ.toDouble(),
            0.0, 1.0, 0.0,
        )

        val viewport = view.viewport
        applyProjection(viewport.width, viewport.height)

        lightEntities.forEach {
            scene.removeEntity(it)
            engine.lightManager.destroy(it)
            EntityManager.get().destroy(it)
        }
        lightEntities.clear()

        next.lights.forEach { light ->
            val colour = Transforms.linearColor(light.color)
            val entity = EntityManager.get().create()

            val type = when (light.type) {
                "point" -> LightManager.Type.POINT
                // Filament has no ambient light type; an indirect fill is
                // modelled as a soft directional from the opposite side, which
                // keeps shadowed faces from going pure black.
                "ambient" -> LightManager.Type.DIRECTIONAL
                else -> LightManager.Type.DIRECTIONAL
            }

            val builder = LightManager.Builder(type)
                .color(colour[0], colour[1], colour[2])
                .intensity(light.intensity)
                .castShadows(light.type == "directional")

            if (type == LightManager.Type.POINT) {
                builder.position(light.x, light.y, light.z)
            } else {
                val sign = if (light.type == "ambient") -1f else 1f
                builder.direction(light.x * sign, light.y * sign, light.z * sign)
            }

            builder.build(engine, entity)
            scene.addEntity(entity)
            lightEntities += entity
        }
    }

    private fun applyProjection(width: Int, height: Int) {
        if (width <= 0 || height <= 0) return
        val aspect = width.toDouble() / height.toDouble()
        val fov = chrome?.fov?.toDouble() ?: 60.0
        camera.setProjection(fov, aspect, 0.05, 1000.0, Camera.Fov.VERTICAL)
    }

    /**
     * Destruction order is load-bearing: assets reference the loaders,
     * the loaders reference the engine, and the engine must go last. Getting
     * this wrong crashes in native code with no usable stack.
     */
    fun destroy() {
        if (destroyed) return
        destroyed = true

        choreographer.removeFrameCallback(frameCallback)
        uiHelper.detach()

        graph.destroy()
        resourceLoader.destroy()
        assetLoader.destroy()
        materialProvider.destroyMaterials()

        lightEntities.forEach {
            scene.removeEntity(it)
            engine.lightManager.destroy(it)
            EntityManager.get().destroy(it)
        }
        lightEntities.clear()

        scene.skybox?.let { engine.destroySkybox(it) }

        // The light before its texture: the light holds the reflections
        // cubemap, and freeing the texture first leaves it dangling.
        environment?.let {
            scene.indirectLight = null
            engine.destroyIndirectLight(it.light)
            engine.destroyTexture(it.texture)
        }
        environment = null

        engine.destroyRenderer(renderer)
        engine.destroyView(view)
        engine.destroyScene(scene)
        engine.destroyCameraComponent(cameraEntity)
        EntityManager.get().destroy(cameraEntity)
        swapChain?.let { engine.destroySwapChain(it) }
        engine.destroy()
    }
}
