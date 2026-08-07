package com.vipertecpro.plugins.scene3d.ui

import kotlin.math.cos
import kotlin.math.sin

/**
 * Column-major 4x4 transforms, the layout Filament's TransformManager expects.
 *
 * Written by hand rather than pulled from a math library so the plugin carries
 * no dependency beyond Filament itself — this is the only linear algebra the
 * renderer needs, and getting the column-major ordering wrong is the classic
 * "my model is sheared" bug, so it is spelled out explicitly below.
 */
internal object Transforms {

    /**
     * Translation * RotationZ * RotationY * RotationX * Scale.
     *
     * Rotation order matters and is fixed to match the PHP side's Euler
     * convention; changing it here without changing Transform.php would make
     * the same scene look different on each platform.
     */
    fun trs(
        x: Float, y: Float, z: Float,
        scaleX: Float, scaleY: Float, scaleZ: Float,
        rotXDeg: Float, rotYDeg: Float, rotZDeg: Float,
    ): FloatArray {
        val rx = Math.toRadians(rotXDeg.toDouble())
        val ry = Math.toRadians(rotYDeg.toDouble())
        val rz = Math.toRadians(rotZDeg.toDouble())

        val cx = cos(rx).toFloat(); val sx = sin(rx).toFloat()
        val cy = cos(ry).toFloat(); val sy = sin(ry).toFloat()
        val cz = cos(rz).toFloat(); val sz = sin(rz).toFloat()

        // Combined ZYX rotation with scale folded into the basis. Each basis
        // VECTOR takes one axis' scale — m0* is the x axis, m1* the y, m2* the
        // z. Scaling by component index instead would scale the world axes
        // rather than the object's own, and a rotated node would shear.
        val m00 = (cz * cy) * scaleX
        val m01 = (sz * cy) * scaleX
        val m02 = (-sy) * scaleX

        val m10 = (cz * sy * sx - sz * cx) * scaleY
        val m11 = (sz * sy * sx + cz * cx) * scaleY
        val m12 = (cy * sx) * scaleY

        val m20 = (cz * sy * cx + sz * sx) * scaleZ
        val m21 = (sz * sy * cx - cz * sx) * scaleZ
        val m22 = (cy * cx) * scaleZ

        // Column-major: each group of four is a COLUMN, and the translation
        // occupies the last one. Filling this row-major transposes the basis
        // and shears everything.
        return floatArrayOf(
            m00, m01, m02, 0f,
            m10, m11, m12, 0f,
            m20, m21, m22, 0f,
            x, y, z, 1f,
        )
    }

    /** Linear interpolation used to drive `move` on the render thread. */
    fun lerp(from: Float, to: Float, t: Float): Float = from + (to - from) * t.coerceIn(0f, 1f)

    /**
     * `#RGB`, `#RRGGBB`, `#RRGGBBAA` — the same grammar the PHP side and EDGE
     * colour props accept, so a colour means one thing across the whole stack.
     * Returns linear-space RGBA, because Filament expects linear and handing it
     * sRGB values is why hand-tinted objects look washed out.
     */
    fun linearColor(hex: String?): FloatArray {
        if (hex.isNullOrBlank()) return floatArrayOf(1f, 1f, 1f, 1f)

        var raw = hex.removePrefix("#")
        if (raw.length == 3) raw = raw.map { "$it$it" }.joinToString("")
        if (raw.length != 6 && raw.length != 8) return floatArrayOf(1f, 1f, 1f, 1f)

        val value = raw.toLongOrNull(16) ?: return floatArrayOf(1f, 1f, 1f, 1f)
        val hasAlpha = raw.length == 8

        val r = ((value shr (if (hasAlpha) 24 else 16)) and 0xFF).toInt() / 255f
        val g = ((value shr (if (hasAlpha) 16 else 8)) and 0xFF).toInt() / 255f
        val b = ((value shr (if (hasAlpha) 8 else 0)) and 0xFF).toInt() / 255f
        val a = if (hasAlpha) (value and 0xFF).toInt() / 255f else 1f

        return floatArrayOf(toLinear(r), toLinear(g), toLinear(b), a)
    }

    /** sRGB -> linear, the standard transfer function. */
    private fun toLinear(c: Float): Float =
        if (c <= 0.04045f) c / 12.92f else Math.pow(((c + 0.055f) / 1.055f).toDouble(), 2.4).toFloat()
}
