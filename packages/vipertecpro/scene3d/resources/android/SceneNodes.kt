package com.vipertecpro.plugins.scene3d.ui

import org.json.JSONArray
import org.json.JSONObject

/**
 * The parsed form of one node from the scene wire format.
 *
 * The wire uses short keys and OMITS defaults, so every read here supplies the
 * same fallback the PHP side assumed when it left them out. Those two lists of
 * defaults are a contract: if one side changes a default the other must too, or
 * objects will quietly drift.
 */
internal data class SceneNode(
    val id: String,
    val revision: Int,
    val model: String?,
    val shape: String?,
    val x: Float, val y: Float, val z: Float,
    val scale: Float,
    val rotX: Float, val rotY: Float, val rotZ: Float,
    val color: String?,
    val metallic: Float,
    val roughness: Float,
    val emissive: Float,
    val opacity: Float,
    val spinAxis: String?, val spinSeconds: Float,
    val moveTo: FloatArray?, val moveSeconds: Float,
    val clip: String?, val clipLoop: Boolean, val clipSpeed: Float,
    val tappable: Boolean,
) {
    /** The asset this node draws — a user model, or a bundled primitive. */
    val assetPath: String
        get() = model ?: "primitives/${shape ?: "box"}.glb"

    companion object {
        fun from(json: JSONObject): SceneNode {
            val spin = json.optJSONObject("spin")
            val move = json.optJSONObject("move")
            val clip = json.optJSONObject("clip")

            return SceneNode(
                id = json.getString("id"),
                revision = json.optInt("r", 0),
                model = json.optString("m").ifBlank { null },
                shape = json.optString("g").ifBlank { null },
                x = json.optDouble("x", 0.0).toFloat(),
                y = json.optDouble("y", 0.0).toFloat(),
                z = json.optDouble("z", 0.0).toFloat(),
                // `s` is absent when the scale is 1 — the PHP side strips it as
                // a default, so 0 here means "unset", not "collapse to a point".
                scale = json.optDouble("s", 0.0).toFloat().let { if (it == 0f) 1f else it },
                rotX = json.optDouble("rx", 0.0).toFloat(),
                rotY = json.optDouble("ry", 0.0).toFloat(),
                rotZ = json.optDouble("rz", 0.0).toFloat(),
                color = json.optJSONObject("mat")?.optString("c")?.ifBlank { null },
                metallic = json.optJSONObject("mat")?.optDouble("me", 0.0)?.toFloat() ?: 0f,
                // 0.5, NOT 0: PHP omits roughness only when it is the default,
                // and the default is a half-rough dielectric. Reading absent as
                // 0 would make every untouched surface a mirror.
                roughness = json.optJSONObject("mat")?.optDouble("ro", 0.5)?.toFloat() ?: 0.5f,
                emissive = json.optJSONObject("mat")?.optDouble("em", 0.0)?.toFloat() ?: 0f,
                opacity = json.optDouble("o", 1.0).toFloat(),
                spinAxis = spin?.optString("a") ?: null,
                spinSeconds = spin?.optDouble("s", 4.0)?.toFloat() ?: 4f,
                moveTo = move?.let {
                    floatArrayOf(
                        it.optDouble("x", 0.0).toFloat(),
                        it.optDouble("y", 0.0).toFloat(),
                        it.optDouble("z", 0.0).toFloat(),
                    )
                },
                moveSeconds = move?.optDouble("s", 1.0)?.toFloat() ?: 1f,
                clip = clip?.optString("n")?.ifBlank { null },
                clipLoop = (clip?.optInt("l", 1) ?: 1) == 1,
                clipSpeed = (clip?.optDouble("sp", 0.0)?.toFloat() ?: 0f).let { if (it == 0f) 1f else it },
                tappable = json.optInt("tap", 0) == 1,
            )
        }
    }
}

/** Camera and lights, which live outside the node graph so a scene update cannot delete them. */
internal data class SceneChrome(
    val camX: Float, val camY: Float, val camZ: Float,
    val fov: Float,
    val targetX: Float, val targetY: Float, val targetZ: Float,
    val background: String?,
    val lights: List<SceneLight>,
)

internal data class SceneLight(
    val type: String,
    val intensity: Float,
    val color: String,
    val x: Float, val y: Float, val z: Float,
)

/**
 * The whole decoded scene.
 *
 * `wireVersion` is checked rather than assumed: a renderer that silently reads
 * a format it does not understand produces a scene that is subtly wrong, which
 * is far harder to diagnose than a refusal.
 */
internal class SceneDocument(
    val wireVersion: Int,
    val chrome: SceneChrome,
    val nodes: List<SceneNode>,
) {
    companion object {
        const val SUPPORTED_WIRE_VERSION = 1

        fun parse(json: String): SceneDocument? {
            val root = runCatching { JSONObject(json) }.getOrNull() ?: return null
            val version = root.optInt("v", 0)
            if (version != SUPPORTED_WIRE_VERSION) return null

            val cam = root.optJSONObject("cam") ?: JSONObject()
            val lights = mutableListOf<SceneLight>()
            val litArray: JSONArray = root.optJSONArray("lit") ?: JSONArray()

            for (i in 0 until litArray.length()) {
                val l = litArray.getJSONObject(i)
                lights += SceneLight(
                    type = l.optString("t", "directional"),
                    intensity = l.optDouble("i", 80000.0).toFloat(),
                    color = l.optString("c", "#FFFFFF"),
                    x = l.optDouble("x", 0.6).toFloat(),
                    y = l.optDouble("y", -1.0).toFloat(),
                    z = l.optDouble("z", -0.8).toFloat(),
                )
            }

            val nodes = mutableListOf<SceneNode>()
            val nodeArray: JSONArray = root.optJSONArray("n") ?: JSONArray()

            for (i in 0 until nodeArray.length()) {
                nodes += SceneNode.from(nodeArray.getJSONObject(i))
            }

            return SceneDocument(
                wireVersion = version,
                chrome = SceneChrome(
                    camX = cam.optDouble("x", 0.0).toFloat(),
                    camY = cam.optDouble("y", 0.0).toFloat(),
                    camZ = cam.optDouble("z", 6.0).toFloat(),
                    fov = cam.optDouble("fov", 60.0).toFloat(),
                    targetX = cam.optDouble("tx", 0.0).toFloat(),
                    targetY = cam.optDouble("ty", 0.0).toFloat(),
                    targetZ = cam.optDouble("tz", 0.0).toFloat(),
                    background = root.optString("bg").ifBlank { null },
                    lights = lights,
                ),
                nodes = nodes,
            )
        }
    }
}
