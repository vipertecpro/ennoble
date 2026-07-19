package com.ennoble.lottie.ui

import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import com.airbnb.lottie.compose.LottieAnimation
import com.airbnb.lottie.compose.LottieCompositionSpec
import com.airbnb.lottie.compose.LottieConstants
import com.airbnb.lottie.compose.animateLottieCompositionAsState
import com.airbnb.lottie.compose.rememberLottieComposition
import com.nativephp.mobile.ui.nativerender.NativeUINode

// Renders a bundled Lottie animation. `source` is the file's base name; the
// copy-animations hook places it under assets/animations/, so it resolves as
// "animations/<source>.json". When `progress` (0.0-1.0) is provided the
// animation is frozen at that frame (timers/progress); otherwise it plays,
// optionally looping.
object LottieRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val p = node.props
        val source = p.getString("source", "")
        if (source.isEmpty()) {
            return
        }

        val loop = p.getBool("loop")
        val speed = p.getDouble("speed", 1.0).toFloat()
        val progress = p.getDouble("progress", -1.0).toFloat()

        val composition by rememberLottieComposition(
            LottieCompositionSpec.Asset("animations/$source.json")
        )

        if (progress >= 0f) {
            LottieAnimation(
                composition = composition,
                progress = { progress },
                modifier = modifier,
            )
        } else {
            val animatedProgress by animateLottieCompositionAsState(
                composition = composition,
                iterations = if (loop) LottieConstants.IterateForever else 1,
                speed = speed,
                isPlaying = true,
            )
            LottieAnimation(
                composition = composition,
                progress = { animatedProgress },
                modifier = modifier,
            )
        }
    }
}
