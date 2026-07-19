import SwiftUI
import Lottie

// Renders a bundled Lottie animation. `source` is the file's base name; the
// copy-animations hook places it in NativePHP/Resources/, so `.named(source)`
// resolves it from the main bundle. When `progress` (0.0–1.0) is provided the
// animation is frozen at that frame (timers/progress); otherwise it plays,
// optionally looping.
struct LottieRenderer: View {
    let node: NativeUINode

    var body: some View {
        let source = node.props.getString("source", default: "")
        let loop = node.props.getBool("loop")
        let speed = node.props.getFloat("speed", default: 1.0)
        let progress = node.props.getFloat("progress", default: -1.0)
        let animation = source.isEmpty ? nil : LottieAnimation.named(source)

        return Group {
            if let animation {
                if progress >= 0 {
                    LottieView(animation: animation)
                        .resizable()
                        .currentProgress(CGFloat(progress))
                } else {
                    LottieView(animation: animation)
                        .resizable()
                        .playing(loopMode: loop ? .loop : .playOnce)
                        .animationSpeed(Double(speed))
                }
            } else {
                Color.clear
            }
        }
        // Fill whatever frame the EDGE layout gives us. Without an explicit
        // fill, a `.resizable()` Lottie can collapse to a zero-size view.
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }
}
