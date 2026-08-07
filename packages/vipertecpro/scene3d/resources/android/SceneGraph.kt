package com.vipertecpro.plugins.scene3d.ui

import android.content.Context
import android.util.Log
import com.google.android.filament.Engine
import com.google.android.filament.Scene
import com.google.android.filament.gltfio.AssetLoader
import com.google.android.filament.gltfio.FilamentAsset
import com.google.android.filament.gltfio.ResourceLoader
import java.nio.ByteBuffer

/**
 * Holds the loaded assets and reconciles them against each incoming scene.
 *
 * THE DIFF IS THE WHOLE POINT. PHP re-sends the entire scene whenever any part
 * of it changes, so rebuilding would reload every glTF and restart every
 * animation on every tick — the exact stutter this architecture exists to
 * avoid. Nodes are matched by id, and a node whose `revision` is unchanged is
 * skipped without even reading its transform.
 */
internal class SceneGraph(
    private val engine: Engine,
    private val scene: Scene,
    private val assetLoader: AssetLoader,
    private val resourceLoader: ResourceLoader,
    private val context: Context,
) {
    private class Entry(
        val asset: FilamentAsset,
        var revision: Int,
        var node: SceneNode,
        /** Where the node was when a `move` began, so it can be interpolated from there. */
        var moveFrom: FloatArray? = null,
        var moveStartedAt: Float = -1f,
    )

    private val entries = mutableMapOf<String, Entry>()

    /** glTF bytes are cached: several nodes usually share one primitive. */
    private val assetBytes = mutableMapOf<String, ByteBuffer?>()

    /**
     * Renderable entity -> node id, for picking. Filament's pick returns the
     * entity it hit and knows nothing about scene3d, so this is the only way
     * back to the id PHP named. Built on load rather than searched on tap: a
     * tap should not walk every asset's entity list.
     */
    private val entityToNode = mutableMapOf<Int, String>()

    fun sync(nodes: List<SceneNode>) {
        val seen = HashSet<String>(nodes.size)

        for (node in nodes) {
            seen += node.id
            val existing = entries[node.id]

            if (existing == null) {
                load(node)?.let { entries[node.id] = it }
                continue
            }

            // Changing what a node IS cannot be patched — the geometry differs
            // — so it is reloaded. Everything else is an in-place update.
            if (existing.node.assetPath != node.assetPath) {
                release(existing)
                entries.remove(node.id)
                load(node)?.let { entries[node.id] = it }
                continue
            }

            if (existing.revision == node.revision) continue

            existing.revision = node.revision
            val hadMove = existing.node.moveTo
            existing.node = node

            // A newly-declared move starts from wherever the node is now.
            if (node.moveTo != null && !node.moveTo.contentEquals(hadMove)) {
                existing.moveFrom = floatArrayOf(node.x, node.y, node.z)
                existing.moveStartedAt = -1f
            }

            applyTransform(existing, node.x, node.y, node.z)
            applyMaterial(existing)
            applyClip(existing)
        }

        val gone = entries.keys.filter { it !in seen }
        for (id in gone) {
            entries.remove(id)?.let { release(it) }
        }
    }

    /**
     * Advance render-thread animations. Called every frame with seconds since
     * the host started, so `spin` and `move` never depend on PHP's cadence.
     */
    fun advance(seconds: Float) {
        for (entry in entries.values) {
            val node = entry.node
            var x = node.x
            var y = node.y
            var z = node.z
            var spinAngle = 0f

            node.moveTo?.let { target ->
                if (entry.moveStartedAt < 0f) entry.moveStartedAt = seconds
                val from = entry.moveFrom ?: floatArrayOf(node.x, node.y, node.z)
                val t = if (node.moveSeconds <= 0f) 1f else (seconds - entry.moveStartedAt) / node.moveSeconds

                x = Transforms.lerp(from[0], target[0], t)
                y = Transforms.lerp(from[1], target[1], t)
                z = Transforms.lerp(from[2], target[2], t)
            }

            if (node.spinAxis != null && node.spinSeconds > 0f) {
                spinAngle = (seconds / node.spinSeconds) * 360f % 360f
            }

            if (node.moveTo == null && spinAngle == 0f) continue

            val rx = node.rotX + if (node.spinAxis == "x") spinAngle else 0f
            val ry = node.rotY + if (node.spinAxis == "y") spinAngle else 0f
            val rz = node.rotZ + if (node.spinAxis == "z") spinAngle else 0f

            setTransform(entry.asset, x, y, z, node.scale, rx, ry, rz)

            entry.asset.instance.animator?.let { animator ->
                if (node.clip != null) {
                    animator.updateBoneMatrices()
                }
            }
        }
    }

    private fun load(node: SceneNode): Entry? {
        val buffer = assetBytes.getOrPut(node.assetPath) { readAsset(node.assetPath) } ?: return null

        // rewind: the same buffer is handed to the loader for every node that
        // shares this asset, and a spent buffer loads as an empty model.
        buffer.rewind()

        val asset = assetLoader.createAsset(buffer) ?: return null

        // MUST be checked, and this is the only way to check it: loadResources
        // returns the loader for chaining, not a status, and gltfio logs a
        // single line and carries on when it cannot resolve something. The
        // unbacked vertex buffer then reaches the GPU and the first frame
        // memcpys from null inside glBufferSubData — the process dies in
        // native code with a stack that names nothing of ours.
        //
        // A non-empty resourceUris means the asset wants files we never
        // supplied (an external .bin or texture). Bundled primitives are GLB
        // and carry none; a user's model might. Refusing here costs one model
        // and turns a fatal signal into a log line.
        val missing = asset.resourceUris
        if (missing.isNotEmpty()) {
            Log.e(
                TAG,
                "[${node.assetPath}] needs external resources this loader cannot supply " +
                    "(${missing.joinToString()}). Use a self-contained .glb. Skipping node ${node.id}.",
            )
            assetLoader.destroyAsset(asset)
            return null
        }

        resourceLoader.loadResources(asset)

        // Source data is only needed while resources resolve; holding it keeps
        // the whole glTF in memory for the life of the asset.
        asset.releaseSourceData()

        scene.addEntities(asset.entities)
        for (renderable in asset.renderableEntities) {
            entityToNode[renderable] = node.id
        }

        val entry = Entry(asset, node.revision, node)
        applyTransform(entry, node.x, node.y, node.z)
        applyMaterial(entry)
        applyClip(entry)

        return entry
    }

    /**
     * Push the node's material onto every material instance the asset owns.
     *
     * The bundled primitives ship a neutral white material precisely so this
     * can tint them; without it every shape renders the same unlit-looking
     * white and the whole Material API does nothing.
     *
     * Each parameter is guarded by hasParameter: gltfio picks an ubershader
     * variant per asset, so a given instance may genuinely not have, say,
     * emissiveFactor, and setting an absent parameter is a native-side error
     * rather than a no-op. Colours are converted to linear because
     * baseColorFactor is a linear value — passing sRGB straight through is the
     * classic "everything looks washed out" bug.
     */
    private fun applyMaterial(entry: Entry) {
        val node = entry.node
        val rgba = Transforms.linearColor(node.color ?: return)

        for (instance in entry.asset.instance.materialInstances) {
            val material = instance.material

            if (material.hasParameter("baseColorFactor")) {
                instance.setParameter("baseColorFactor", rgba[0], rgba[1], rgba[2], rgba[3] * node.opacity)
            }

            if (material.hasParameter("metallicFactor")) {
                instance.setParameter("metallicFactor", node.metallic)
            }

            if (material.hasParameter("roughnessFactor")) {
                instance.setParameter("roughnessFactor", node.roughness)
            }

            // glTF's emissiveFactor is a colour, not a scalar: the node's
            // strength scales its own colour so a glowing object glows in the
            // hue it already is.
            if (node.emissive > 0f && material.hasParameter("emissiveFactor")) {
                instance.setParameter(
                    "emissiveFactor",
                    rgba[0] * node.emissive,
                    rgba[1] * node.emissive,
                    rgba[2] * node.emissive,
                )
            }
        }
    }

    private fun applyClip(entry: Entry) {
        val name = entry.node.clip ?: return
        val animator = entry.asset.instance.animator ?: return

        for (i in 0 until animator.animationCount) {
            if (animator.getAnimationName(i) == name) {
                animator.applyAnimation(i, 0f)
                animator.updateBoneMatrices()
                return
            }
        }
    }

    private fun applyTransform(entry: Entry, x: Float, y: Float, z: Float) {
        val n = entry.node
        setTransform(entry.asset, x, y, z, n.scale, n.rotX, n.rotY, n.rotZ)
    }

    private fun setTransform(
        asset: FilamentAsset,
        x: Float, y: Float, z: Float,
        scale: Float,
        rx: Float, ry: Float, rz: Float,
    ) {
        val tm = engine.transformManager
        val instance = tm.getInstance(asset.root)
        if (instance == 0) return

        tm.setTransform(instance, Transforms.trs(x, y, z, scale, rx, ry, rz))
    }

    private companion object {
        const val TAG = "Scene3d"
    }

    private fun readAsset(path: String): ByteBuffer? = runCatching {
        context.assets.open(path).use { stream ->
            val bytes = stream.readBytes()
            ByteBuffer.allocateDirect(bytes.size).apply {
                put(bytes)
                rewind()
            }
        }
    }.onFailure { Log.e(TAG, "Asset [$path] is not bundled — is the copy_assets hook running?", it) }
        .getOrNull()

    /** The node a picked renderable belongs to, or null if it is not ours. */
    fun nodeFor(renderable: Int): SceneNode? =
        entityToNode[renderable]?.let { entries[it]?.node }

    private fun release(entry: Entry) {
        for (renderable in entry.asset.renderableEntities) {
            entityToNode.remove(renderable)
        }
        scene.removeEntities(entry.asset.entities)
        assetLoader.destroyAsset(entry.asset)
    }

    fun destroy() {
        entries.values.forEach { release(it) }
        entries.clear()
        assetBytes.clear()
    }
}
