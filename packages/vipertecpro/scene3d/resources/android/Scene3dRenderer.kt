package com.vipertecpro.plugins.scene3d.ui

import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.viewinterop.AndroidView
import com.google.android.filament.utils.Utils
import com.nativephp.mobile.ui.nativerender.NativeElementBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode

/**
 * `<native:scene-3d>` on Android.
 *
 * The element is a thin shell: it reads the `scene` prop, parses it, and hands
 * it to a [FilamentHost] that survives recomposition. Everything expensive —
 * the engine, the loaded assets, the frame loop — lives in the host, so the
 * neighbouring EDGE tree re-rendering (which happens on every PHP tick) costs
 * one string comparison here rather than a scene rebuild.
 */
object Scene3dRenderer {

    init {
        // Loads Filament's native libraries. Safe to call repeatedly; the
        // first Engine.create() without it crashes with UnsatisfiedLinkError.
        Utils.init()
    }

    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val context = LocalContext.current
        val p = node.props
        val json = p.getString("scene", "{}")

        val host = remember { FilamentHost(context) }

        DisposableEffect(host) {
            onDispose { host.destroy() }
        }

        // Re-bound whenever the callback id changes. The lambda closes over
        // node.id, so it must not be captured once and kept — a recomposition
        // that renumbers the element would otherwise report taps against a
        // stale id and PHP would route them to nothing.
        val tapCallback = p.getCallbackId("on_node_tap")
        DisposableEffect(host, tapCallback, node.id) {
            host.onNodeTap = if (tapCallback != 0) {
                { picked -> NativeElementBridge.sendTextChangeEvent(tapCallback, node.id, picked.id) }
            } else {
                null
            }

            onDispose { host.onNodeTap = null }
        }

        // Parse only when the payload actually changed. PHP re-sends the whole
        // scene on any change, and neighbouring re-renders resend it unchanged.
        val document = remember(json) { SceneDocument.parse(json) }

        AndroidView(
            factory = { host.surfaceView },
            modifier = modifier,
            update = { document?.let(host::apply) },
        )
    }
}
