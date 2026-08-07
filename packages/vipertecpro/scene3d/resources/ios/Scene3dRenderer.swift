import SwiftUI
import SceneKit
import UIKit

/// `<native:scene-3d>` on iOS.
///
/// WHY SCENEKIT AND NOT FILAMENT. Android renders through Filament because
/// Filament is what gives Android a glTF loader with precompiled materials. On
/// iOS that reasoning inverts: SceneKit ships WITH the system, needs no
/// CocoaPod, and — decisively — its built-in geometries map one-to-one onto
/// this plugin's seven primitives, which is all any scene here actually uses.
/// Adding a Filament pod would put a large third-party dependency into an app
/// that already builds, to gain a model loader nothing is asking for yet.
///
/// The trade is real and is written down rather than hidden: `Node::model()`
/// (glTF) is ANDROID-ONLY for now. A scene that asks for a model on iOS gets
/// a logged warning and no node, not a crash.
struct Scene3dRenderer: View {
    let node: NativeUINode

    var body: some View {
        let json = node.props.getString("scene", default: "{}")
        let tapCallback = node.props.getCallbackId("on_node_tap")

        Scene3dView(json: json, tapCallback: tapCallback, nodeId: node.id)
            .frame(maxWidth: .infinity, maxHeight: .infinity)
    }
}

/// The SceneKit host, kept alive across SwiftUI updates by the coordinator.
private struct Scene3dView: UIViewRepresentable {
    let json: String
    let tapCallback: Int
    let nodeId: Int

    func makeCoordinator() -> Scene3dCoordinator {
        Scene3dCoordinator()
    }

    func makeUIView(context: Context) -> SCNView {
        let view = SCNView()
        view.scene = context.coordinator.scene
        view.pointOfView = context.coordinator.cameraNode
        view.antialiasingMode = .multisampling2X
        // The scene supplies its own lights; SceneKit's default light would
        // silently add a second, unaccounted-for one.
        view.autoenablesDefaultLighting = false
        view.isUserInteractionEnabled = true
        view.backgroundColor = .clear

        let recognizer = UITapGestureRecognizer(
            target: context.coordinator,
            action: #selector(Scene3dCoordinator.handleTap(_:))
        )
        view.addGestureRecognizer(recognizer)

        return view
    }

    func updateUIView(_ view: SCNView, context: Context) {
        context.coordinator.tapCallback = tapCallback
        context.coordinator.elementId = nodeId
        // Parse only when the payload actually changed. PHP re-sends the whole
        // scene on any change, and a neighbouring re-render resends it
        // unchanged.
        context.coordinator.apply(json: json, to: view)
    }
}

final class Scene3dCoordinator: NSObject {
    let scene = SCNScene()
    let cameraNode = SCNNode()

    var tapCallback: Int = 0
    var elementId: Int = 0

    /// id -> (node, revision). The revision is a content hash from PHP, so a
    /// node whose revision matches is skipped without reading anything else.
    private var entries: [String: (node: SCNNode, revision: Int)] = [:]

    /// Which ids may be picked. The hit test happily reports the ground plane
    /// behind the object, so without this a tap anywhere resolves to something.
    private var tappable: Set<String> = []
    private var lightNodes: [SCNNode] = []
    private var appliedJson: String?
    private var appliedChrome: Scene3dChrome?

    override init() {
        super.init()
        cameraNode.camera = SCNCamera()
        cameraNode.camera?.zNear = 0.05
        cameraNode.camera?.zFar = 1000
        scene.rootNode.addChildNode(cameraNode)
    }

    func apply(json: String, to view: SCNView) {
        guard json != appliedJson else { return }
        appliedJson = json

        guard let document = Scene3dDocument.parse(json) else { return }

        if appliedChrome != document.chrome {
            applyChrome(document.chrome, to: view)
            appliedChrome = document.chrome
        }

        sync(document.nodes)
    }

    private func applyChrome(_ chrome: Scene3dChrome, to view: SCNView) {
        // The background is set on the VIEW, not the scene, so a translucent
        // colour composites against the app behind it instead of against
        // black — which is what lets the viewport sit in the page.
        view.backgroundColor = chrome.background ?? .clear
        scene.background.contents = chrome.background

        cameraNode.position = chrome.camera
        cameraNode.look(at: chrome.target, up: SCNVector3(0, 1, 0), localFront: SCNVector3(0, 0, -1))
        cameraNode.camera?.fieldOfView = chrome.fieldOfView

        // Without an environment, SceneKit's physically-based materials have
        // only direct light to work with and render far darker than intended.
        // The background doubles as the environment, exactly as it does on
        // Android — so the scene picks up the app's own colour as ambient.
        scene.lightingEnvironment.contents = chrome.background
        scene.lightingEnvironment.intensity = 1.0

        lightNodes.forEach { $0.removeFromParentNode() }
        lightNodes = chrome.lights.map { light in
            let scnLight = SCNLight()
            scnLight.color = light.color
            scnLight.intensity = light.intensity / 40

            let node = SCNNode()

            switch light.type {
            case "point":
                scnLight.type = .omni
                // For a point light x/y/z IS a position.
                node.position = SCNVector3(light.x, light.y, light.z)
            case "ambient":
                // SceneKit HAS a real ambient light, so the fill is a genuine
                // ambient here rather than the opposite-facing directional
                // that Android has to fake it with.
                scnLight.type = .ambient
            default:
                scnLight.type = .directional
                scnLight.castsShadow = true
                scnLight.shadowMode = .deferred
                // SceneKit's default shadow is near-opaque black, which on a
                // flat-shaded scene reads as a solid slab stuck to the object
                // rather than as a shadow. Soft and translucent is the whole
                // difference between depth and a smear.
                scnLight.shadowRadius = 12
                scnLight.shadowColor = UIColor(white: 0, alpha: 0.28)
                scnLight.shadowSampleCount = 16
                // A directional shadow map covers orthographicScale units.
                // The default is 1, which for a board sixteen cells tall
                // shadows almost nothing and stipples what it does reach.
                scnLight.orthographicScale = 24
                scnLight.zFar = 200
                aim(node, along: light)
            }

            node.light = scnLight
            scene.rootNode.addChildNode(node)

            return node
        }
    }

    /// Point a directional light along its direction vector.
    ///
    /// For a directional light x/y/z is a DIRECTION, not a position — the same
    /// vector Android hands to Filament's `direction()`. SceneKit has no
    /// direction property: a light shines along its node's -Z axis. So the node
    /// sits at the origin and looks AT the direction, which puts -Z on it.
    ///
    /// Treating the vector as a position and aiming back at the origin — the
    /// obvious reading — points the light the exact opposite way, and every
    /// surface facing the camera goes black.
    private func aim(_ node: SCNNode, along light: Scene3dLight) {
        let direction = SCNVector3(light.x, light.y, light.z)

        // `look(at:)` degenerates when the direction is parallel to up, which a
        // straight-down key light is.
        let up: SCNVector3 = abs(light.x) < 0.001 && abs(light.z) < 0.001
            ? SCNVector3(0, 0, 1)
            : SCNVector3(0, 1, 0)

        node.position = SCNVector3Zero
        node.look(at: direction, up: up, localFront: SCNVector3(0, 0, -1))
    }

    private func sync(_ nodes: [Scene3dNode]) {
        var seen = Set<String>(minimumCapacity: nodes.count)

        for node in nodes {
            seen.insert(node.id)

            if let existing = entries[node.id] {
                if existing.revision == node.revision { continue }

                apply(node, to: existing.node)
                entries[node.id] = (existing.node, node.revision)
                setTappable(node)

                continue
            }

            guard let created = make(node) else { continue }

            scene.rootNode.addChildNode(created)
            apply(node, to: created)
            entries[node.id] = (created, node.revision)
            setTappable(node)
        }

        for (id, entry) in entries where !seen.contains(id) {
            entry.node.removeFromParentNode()
            entries.removeValue(forKey: id)
            tappable.remove(id)
        }
    }

    private func setTappable(_ node: Scene3dNode) {
        if node.tappable {
            tappable.insert(node.id)
        } else {
            tappable.remove(node.id)
        }
    }

    private func make(_ node: Scene3dNode) -> SCNNode? {
        if node.model != nil {
            NSLog("[scene3d] glTF models are Android-only for now; skipping node \(node.id)")

            return nil
        }

        let scnNode = SCNNode(geometry: geometry(for: node.shape))
        scnNode.name = node.id

        return scnNode
    }

    /// The seven primitives, as SceneKit's own geometry. Sized to one unit so
    /// scale() means the same thing here as it does on Android, where the same
    /// shapes are bundled meshes.
    private func geometry(for shape: String) -> SCNGeometry {
        switch shape {
        case "sphere":
            return SCNSphere(radius: 0.5)
        case "capsule":
            return SCNCapsule(capRadius: 0.3, height: 1.2)
        case "cylinder":
            return SCNCylinder(radius: 0.4, height: 1.0)
        case "cone":
            return SCNCone(topRadius: 0, bottomRadius: 0.5, height: 1.0)
        case "torus":
            return SCNTorus(ringRadius: 0.35, pipeRadius: 0.15)
        case "plane":
            return SCNPlane(width: 1.0, height: 1.0)
        default:
            return SCNBox(width: 1, height: 1, length: 1, chamferRadius: 0.02)
        }
    }

    private func apply(_ node: Scene3dNode, to scnNode: SCNNode) {
        scnNode.position = node.position
        scnNode.scale = node.scale
        scnNode.eulerAngles = node.euler
        scnNode.opacity = node.opacity

        if let material = scnNode.geometry?.firstMaterial {
            material.lightingModel = .physicallyBased

            if let color = node.color {
                material.diffuse.contents = color
                material.emission.contents = node.emissive > 0
                    ? color.withAlphaComponent(min(1, node.emissive))
                    : UIColor.black
            }

            // NSNumber explicitly: `contents` is Any?, and handing it a raw
            // CGFloat leans on bridging to mean "a scalar" rather than saying so.
            material.metalness.contents = NSNumber(value: Double(node.metallic))
            material.roughness.contents = NSNumber(value: Double(node.roughness))
        }

        // Actions are replaced wholesale rather than added to: this method
        // runs again every time the node changes, and adding would stack a
        // second spin on top of the first each time.
        scnNode.removeAllActions()

        if let axis = node.spinAxis, node.spinSeconds > 0 {
            let turn = SCNAction.rotateBy(
                x: axis == "x" ? .pi * 2 : 0,
                y: axis == "y" ? .pi * 2 : 0,
                z: axis == "z" ? .pi * 2 : 0,
                duration: node.spinSeconds
            )
            turn.timingMode = .linear
            scnNode.runAction(.repeatForever(turn))
        }

        if let target = node.moveTo, node.moveSeconds > 0 {
            scnNode.runAction(.move(to: target, duration: node.moveSeconds))
        }
    }

    @objc func handleTap(_ recognizer: UITapGestureRecognizer) {
        guard tapCallback != 0, let view = recognizer.view as? SCNView else { return }

        let point = recognizer.location(in: view)

        // Nearest FIRST, then the first that is actually tappable. Asking for
        // firstFoundOnly would return whatever is nearest even when it cannot
        // be picked, and the tap would be swallowed by the scenery.
        for hit in view.hitTest(point, options: [SCNHitTestOption.searchMode: SCNHitTestSearchMode.all.rawValue]) {
            guard let id = hit.node.name, tappable.contains(id) else { continue }

            NativeElementBridge.sendTextChangeEvent(tapCallback, nodeId: elementId, text: id)

            return
        }
    }
}
