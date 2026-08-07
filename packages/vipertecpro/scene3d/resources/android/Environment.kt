package com.vipertecpro.plugins.scene3d.ui

import com.google.android.filament.Engine
import com.google.android.filament.IndirectLight
import com.google.android.filament.Texture
import java.nio.ByteBuffer
import java.nio.ByteOrder

/**
 * The environment that metals reflect.
 *
 * WHY THIS EXISTS AT ALL. A metallic surface has no diffuse response — in PBR
 * it is defined entirely by what it reflects. With only direct lights in the
 * scene there is nothing to reflect, so every metal renders BLACK, which reads
 * as a broken renderer rather than as the physically correct result it is.
 * Filament will not invent an environment for you, and the usual answer is to
 * ship a prefiltered IBL cubemap — a binary asset, per app, that an author has
 * to produce with `cmgen`.
 *
 * This builds one procedurally instead, so the plugin has a sane default with
 * no asset pipeline: a sky-to-ground gradient, which is what a real sky mostly
 * is, and enough to give metals something to pick up.
 *
 * WHY THE MIPS CAN BE FAKED. A reflection cubemap's mip chain is normally a
 * roughness-prefiltered sequence, and computing it properly is expensive. But
 * blurring a smooth gradient returns approximately the same gradient — so each
 * level is generated at its own size from the same function, and the result is
 * close enough that no one can tell. This would NOT hold for a detailed
 * environment; if this ever loads a real HDR, it needs real prefiltering.
 */
internal object Environment {

    /** Enough levels to reach 1x1 from [SIZE], which Filament requires. */
    private const val SIZE = 32

    private const val LEVELS = 6

    /** Filament's cubemap face order. Do not reorder — the offsets follow it. */
    private val FACES = arrayOf("+x", "-x", "+y", "-y", "+z", "-z")

    class Built(val light: IndirectLight, val texture: Texture)

    /**
     * @param sky   colour at straight up
     * @param ground colour at straight down
     */
    fun build(engine: Engine, sky: FloatArray, ground: FloatArray, intensity: Float): Built {
        val texture = Texture.Builder()
            .width(SIZE)
            .height(SIZE)
            .levels(LEVELS)
            .sampler(Texture.Sampler.SAMPLER_CUBEMAP)
            .format(Texture.InternalFormat.RGBA8)
            .build(engine)

        var size = SIZE
        for (level in 0 until LEVELS) {
            val faceBytes = size * size * 4
            val buffer = ByteBuffer.allocateDirect(faceBytes * 6).order(ByteOrder.nativeOrder())

            for (face in FACES.indices) {
                for (y in 0 until size) {
                    for (x in 0 until size) {
                        // Face-local coords in [-1, 1], sampled at texel centres.
                        val u = 2f * ((x + 0.5f) / size) - 1f
                        val v = 1f - 2f * ((y + 0.5f) / size)
                        val dir = direction(face, u, v)

                        // Only the vertical component matters for a gradient;
                        // remapped from [-1, 1] to [0, 1] with a soft curve so
                        // the horizon is a band rather than a hard line.
                        val t = ((dir[1] + 1f) * 0.5f).let { it * it * (3f - 2f * it) }

                        buffer.put((mix(ground[0], sky[0], t) * 255f).toInt().toByte())
                        buffer.put((mix(ground[1], sky[1], t) * 255f).toInt().toByte())
                        buffer.put((mix(ground[2], sky[2], t) * 255f).toInt().toByte())
                        buffer.put(255.toByte())
                    }
                }
            }
            buffer.flip()

            val offsets = IntArray(6) { it * faceBytes }
            texture.setImage(
                engine,
                level,
                Texture.PixelBufferDescriptor(buffer, Texture.Format.RGBA, Texture.Type.UBYTE),
                offsets,
            )

            size /= 2
        }

        // Diffuse ambient is supplied as a single band-0 spherical harmonic —
        // a uniform term — rather than derived from the cubemap. Irradiance
        // from a smooth gradient IS very nearly uniform, and one constant is
        // far cheaper than a convolution.
        val ambient = floatArrayOf(
            mix(ground[0], sky[0], 0.5f),
            mix(ground[1], sky[1], 0.5f),
            mix(ground[2], sky[2], 0.5f),
        )

        val light = IndirectLight.Builder()
            .reflections(texture)
            .irradiance(1, ambient)
            .intensity(intensity)
            .build(engine)

        return Built(light, texture)
    }

    /**
     * World direction for a texel on a cubemap face, in Filament's face order.
     * Getting this wrong does not fail — it just rotates the sky, which is
     * invisible on a gradient and maddening on a real environment.
     */
    private fun direction(face: Int, u: Float, v: Float): FloatArray = when (face) {
        0 -> floatArrayOf(1f, v, -u)   // +X
        1 -> floatArrayOf(-1f, v, u)   // -X
        2 -> floatArrayOf(u, 1f, -v)   // +Y
        3 -> floatArrayOf(u, -1f, v)   // -Y
        4 -> floatArrayOf(u, v, 1f)    // +Z
        else -> floatArrayOf(-u, v, -1f) // -Z
    }

    private fun mix(a: Float, b: Float, t: Float): Float = a + (b - a) * t
}
