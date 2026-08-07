import SwiftUI
import SceneKit

/// A real 3D viewport, backed by SceneKit.
///
/// WHY SCENEKIT. It is part of the iOS SDK, so the plugin adds no runtime and
/// no build step — unlike Filament, whose materials must be compiled offline
/// with `matc`, or a full engine, which would add ~110MB and could only render
/// full-screen. This composes inside an EDGE tree like any other element.
///
/// WHY THE SCENE IS DIFFED RATHER THAN REBUILT. PHP re-sends the whole scene
/// whenever any of it changes, and rebuilding would restart every running
/// animation on every tick. Instead nodes are matched by id: new ones are
/// added, missing ones removed, and survivors updated in place — so a node
/// carrying `spin` keeps spinning across unrelated updates. This is the same
/// contract EDGE transforms follow, where re-applying identical props must be
/// a no-op.
struct Scene3dRenderer: View {
    let node: NativeUINode

    var body: some View {
        let p = node.props
        let json = p.getString("scene", default: "{}")
        let background = p.getColor("background", default: 0)
        let distance = p.getFloat("camera_distance", default: 6)
        let fov = p.getFloat("camera_fov", default: 60)
        let onNodeTap = p.getCallbackId("on_node_tap")

        SceneKitViewport(
            sceneJSON: json,
            background: background,
            cameraDistance: CGFloat(distance),
            fieldOfView: CGFloat(fov),
            onNodeTap: onNodeTap,
            nodeId: node.id
        )
    }
}

private struct SceneKitViewport: UIViewRepresentable {
    let sceneJSON: String
    let background: Int
    let cameraDistance: CGFloat
    let fieldOfView: CGFloat
    let onNodeTap: Int
    let nodeId: String

    func makeCoordinator() -> Coordinator {
        Coordinator(onNodeTap: onNodeTap, nodeId: nodeId)
    }

    func makeUIView(context: Context) -> SCNView {
        let view = SCNView()
        let scene = SCNScene()
        view.scene = scene
        view.antialiasingMode = .multisampling2X
        view.isUserInteractionEnabled = true
        view.backgroundColor = background != 0 ? UIColor(argb: background) : .clear

        // A camera and a key light live outside the PHP-described graph: they
        // are viewport furniture, not scene content, so PHP never has to
        // describe them and can never accidentally delete them.
        let camera = SCNNode()
        camera.camera = SCNCamera()
        camera.camera?.fieldOfView = fieldOfView
        camera.position = SCNVector3(0, 0, Float(cameraDistance))
        camera.name = "__camera"
        scene.rootNode.addChildNode(camera)

        let key = SCNNode()
        key.light = SCNLight()
        key.light?.type = .omni
        key.light?.intensity = 900
        key.position = SCNVector3(4, 6, 8)
        key.name = "__key"
        scene.rootNode.addChildNode(key)

        let ambient = SCNNode()
        ambient.light = SCNLight()
        ambient.light?.type = .ambient
        ambient.light?.intensity = 320
        ambient.name = "__ambient"
        scene.rootNode.addChildNode(ambient)

        context.coordinator.scene = scene
        context.coordinator.cameraNode = camera

        let tap = UITapGestureRecognizer(
            target: context.coordinator,
            action: #selector(Coordinator.handleTap(_:))
        )
        view.addGestureRecognizer(tap)

        context.coordinator.apply(json: sceneJSON)

        return view
    }

    func updateUIView(_ view: SCNView, context: Context) {
        view.backgroundColor = background != 0 ? UIColor(argb: background) : .clear
        context.coordinator.cameraNode?.camera?.fieldOfView = fieldOfView
        context.coordinator.cameraNode?.position.z = Float(cameraDistance)
        context.coordinator.onNodeTap = onNodeTap
        context.coordinator.apply(json: sceneJSON)
    }

    final class Coordinator: NSObject {
        var scene: SCNScene?
        var cameraNode: SCNNode?
        var onNodeTap: Int
        let nodeId: String

        /// Nodes currently in the graph, by the id PHP gave them.
        private var managed: [String: SCNNode] = [:]

        /// The last scene applied. Comparing the raw string first means an
        /// unchanged re-render costs one comparison instead of a JSON parse
        /// and a full graph walk — and Home-style neighbours re-render often.
        private var lastJSON: String?

        init(onNodeTap: Int, nodeId: String) {
            self.onNodeTap = onNodeTap
            self.nodeId = nodeId
        }

        func apply(json: String) {
            guard json != lastJSON else { return }
            lastJSON = json

            guard
                let scene,
                let data = json.data(using: .utf8),
                let root = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
            else { return }

            let descriptors = (root["nodes"] as? [[String: Any]]) ?? []
            var seen = Set<String>()

            for descriptor in descriptors {
                guard let id = descriptor["id"] as? String else { continue }
                seen.insert(id)

                let target = managed[id] ?? {
                    let created = SCNNode()
                    created.name = id
                    managed[id] = created
                    scene.rootNode.addChildNode(created)
                    return created
                }()

                update(target, from: descriptor, isNew: target.geometry == nil)
            }

            for (id, obsolete) in managed where !seen.contains(id) {
                obsolete.removeAllActions()
                obsolete.removeFromParentNode()
                managed.removeValue(forKey: id)
            }
        }

        private func update(_ target: SCNNode, from d: [String: Any], isNew: Bool) {
            let f = { (key: String, fallback: CGFloat) -> CGFloat in
                (d[key] as? NSNumber).map { CGFloat(truncating: $0) } ?? fallback
            }

            // Geometry only when the shape changes: rebuilding it every update
            // would throw away the material and flicker.
            let shape = (d["shape"] as? String) ?? "box"
            if isNew || target.value(forKey: "shapeKind") as? String != shape {
                target.geometry = Self.geometry(for: shape)
                target.setValue(shape, forKey: "shapeKind")
            }

            if let hex = d["color"] as? String, let colour = UIColor(hexString: hex) {
                let material = target.geometry?.firstMaterial ?? SCNMaterial()
                material.diffuse.contents = colour
                material.lightingModel = .physicallyBased
                material.roughness.contents = 0.45
                target.geometry?.firstMaterial = material
            }

            target.position = SCNVector3(f("x", 0), f("y", 0), f("z", 0))
            let scale = f("scale", 1)
            target.scale = SCNVector3(scale, scale, scale)
            target.eulerAngles = SCNVector3(
                f("rx", 0) * .pi / 180,
                f("ry", 0) * .pi / 180,
                f("rz", 0) * .pi / 180
            )
            target.opacity = f("opacity", 1)

            // Animations are declared, not driven: once attached they run on
            // SceneKit's own clock and survive every later scene update, so
            // PHP's slow logic tick can never gate them.
            if let spin = d["spin"] as? [String: Any] {
                let seconds = (spin["seconds"] as? NSNumber).map { CGFloat(truncating: $0) } ?? 4
                let axis = (spin["axis"] as? String) ?? "y"
                let key = "spin-\(axis)-\(seconds)"

                if target.action(forKey: key) == nil {
                    target.removeAllActions()
                    let rotate = SCNAction.rotateBy(
                        x: axis == "x" ? .pi * 2 : 0,
                        y: axis == "y" ? .pi * 2 : 0,
                        z: axis == "z" ? .pi * 2 : 0,
                        duration: TimeInterval(seconds)
                    )
                    rotate.timingMode = .linear
                    target.runAction(.repeatForever(rotate), forKey: key)
                }
            }

            if let tween = d["tween"] as? [String: Any], let to = tween["to"] as? [String: Any] {
                let seconds = (tween["seconds"] as? NSNumber).map { CGFloat(truncating: $0) } ?? 1
                let key = "tween"

                if target.action(forKey: key) == nil {
                    let destination = SCNVector3(
                        (to["x"] as? NSNumber).map { CGFloat(truncating: $0) } ?? CGFloat(target.position.x),
                        (to["y"] as? NSNumber).map { CGFloat(truncating: $0) } ?? CGFloat(target.position.y),
                        (to["z"] as? NSNumber).map { CGFloat(truncating: $0) } ?? CGFloat(target.position.z)
                    )
                    let move = SCNAction.move(to: destination, duration: TimeInterval(seconds))
                    move.timingMode = .linear
                    target.runAction(move, forKey: key)
                }
            }
        }

        private static func geometry(for shape: String) -> SCNGeometry {
            switch shape {
            case "sphere":   return SCNSphere(radius: 0.5)
            case "capsule":  return SCNCapsule(capRadius: 0.3, height: 1.2)
            case "cylinder": return SCNCylinder(radius: 0.4, height: 1.0)
            case "cone":     return SCNCone(topRadius: 0, bottomRadius: 0.5, height: 1.0)
            case "torus":    return SCNTorus(ringRadius: 0.5, pipeRadius: 0.18)
            case "pyramid":  return SCNPyramid(width: 0.9, height: 0.9, length: 0.9)
            default:         return SCNBox(width: 0.9, height: 0.9, length: 0.9, chamferRadius: 0.06)
            }
        }

        @objc func handleTap(_ recogniser: UITapGestureRecognizer) {
            guard onNodeTap != 0, let view = recogniser.view as? SCNView else { return }

            let point = recogniser.location(in: view)
            let hits = view.hitTest(point, options: [.boundingBoxOnly: false])

            // Walk up from the hit: geometry may sit on a child of the node
            // PHP named, and PHP can only act on ids it actually issued.
            for hit in hits {
                var candidate: SCNNode? = hit.node
                while let current = candidate {
                    if let name = current.name, !name.hasPrefix("__") {
                        NativeElementBridge.sendTextChangeEvent(onNodeTap, nodeId: nodeId, text: name)
                        return
                    }
                    candidate = current.parent
                }
            }
        }
    }
}

private extension UIColor {
    /// `#RGB`, `#RRGGBB` and `#RRGGBBAA` — the same grammar EDGE colour props
    /// accept, so a colour written in PHP means the same thing here.
    convenience init?(hexString: String) {
        var hex = hexString.trimmingCharacters(in: .whitespacesAndNewlines)
        if hex.hasPrefix("#") { hex.removeFirst() }

        if hex.count == 3 {
            hex = hex.map { "\($0)\($0)" }.joined()
        }

        guard hex.count == 6 || hex.count == 8, let value = UInt64(hex, radix: 16) else {
            return nil
        }

        let hasAlpha = hex.count == 8
        let r = CGFloat((value >> (hasAlpha ? 24 : 16)) & 0xFF) / 255
        let g = CGFloat((value >> (hasAlpha ? 16 : 8)) & 0xFF) / 255
        let b = CGFloat((value >> (hasAlpha ? 8 : 0)) & 0xFF) / 255
        let a = hasAlpha ? CGFloat(value & 0xFF) / 255 : 1

        self.init(red: r, green: g, blue: b, alpha: a)
    }
}
