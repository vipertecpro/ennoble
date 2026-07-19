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
        let speed = CGFloat(node.props.getFloat("speed", default: 1.0))
        let progress = CGFloat(node.props.getFloat("progress", default: -1.0))

        if source.isEmpty {
            Color.clear
        } else if progress >= 0 {
            LottieView(animation: .named(source))
                .resizable()
                .currentProgress(progress)
        } else if loop {
            LottieView(animation: .named(source))
                .resizable()
                .looping()
                .animationSpeed(speed)
        } else {
            LottieView(animation: .named(source))
                .resizable()
                .playing()
                .animationSpeed(speed)
        }
    }
}
