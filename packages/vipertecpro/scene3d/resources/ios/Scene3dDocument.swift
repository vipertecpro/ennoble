import Foundation
import SceneKit
import UIKit

/// The wire format, parsed.
///
/// Deliberately a mirror of the Kotlin `SceneNodes.kt`, key for key. The wire
/// OMITS defaults, so every read here restates the default PHP assumed — those
/// two lists are a contract, and `s` absent meaning scale 1 (not 0) is the
/// sharpest edge in it.
enum Scene3dWire {
    /// Refuse a payload from a newer PHP than this renderer understands.
    /// Silently drawing a format you do not know is far harder to diagnose
    /// than a refusal.
    static let supportedVersion = 1
}

struct Scene3dNode {
    let id: String
    /// Content hash from PHP. A node whose revision is unchanged is skipped
    /// without reading anything else.
    let revision: Int
    let model: String?
    let shape: String
    let position: SCNVector3
    let scale: SCNVector3
    let euler: SCNVector3
    let color: UIColor?
    let metallic: CGFloat
    let roughness: CGFloat
    let emissive: CGFloat
    let opacity: CGFloat
    let spinAxis: String?
    let spinSeconds: Double
    let moveTo: SCNVector3?
    let moveSeconds: Double
    let tappable: Bool

    static func parse(_ json: [String: Any]) -> Scene3dNode? {
        guard let id = json["id"] as? String else { return nil }

        let material = json["mat"] as? [String: Any]
        let move = json["move"] as? [String: Any]
        let spin = json["spin"] as? [String: Any]

        // `s` absent means 1, not 0 — PHP strips it as a default. Reading it as
        // 0 would collapse every untouched node to a point.
        let uniform = number(json["s"], 0) == 0 ? 1 : number(json["s"], 1)

        return Scene3dNode(
            id: id,
            revision: Int(number(json["r"], 0)),
            model: json["m"] as? String,
            shape: (json["g"] as? String) ?? "box",
            position: SCNVector3(number(json["x"], 0), number(json["y"], 0), number(json["z"], 0)),
            scale: SCNVector3(uniform, number(json["sy"], 0) == 0 ? uniform : number(json["sy"], 1),
                              number(json["sz"], 0) == 0 ? uniform : number(json["sz"], 1)),
            euler: SCNVector3(radians(number(json["rx"], 0)), radians(number(json["ry"], 0)), radians(number(json["rz"], 0))),
            color: color(material?["c"] as? String),
            metallic: CGFloat(number(material?["me"], 0)),
            // Absent roughness means 0.5, the default dielectric — reading it
            // as 0 would make every untouched surface a mirror.
            roughness: CGFloat(number(material?["ro"], 0) == 0 ? 0.5 : number(material?["ro"], 0.5)),
            emissive: CGFloat(number(material?["em"], 0)),
            opacity: CGFloat(number(json["o"], 0) == 0 ? 1 : number(json["o"], 1)),
            spinAxis: spin?["a"] as? String,
            spinSeconds: Double(number(spin?["s"], 4)),
            moveTo: move.map { SCNVector3(number($0["x"], 0), number($0["y"], 0), number($0["z"], 0)) },
            moveSeconds: Double(number(move?["s"], 1)),
            tappable: Int(number(json["tap"], 0)) == 1
        )
    }

    static func number(_ value: Any?, _ fallback: Float) -> Float {
        (value as? NSNumber)?.floatValue ?? fallback
    }

    private static func radians(_ degrees: Float) -> Float {
        degrees * .pi / 180
    }

    /// `#RGB`, `#RRGGBB` and `#RRGGBBAA` — the same grammar PHP authors in.
    static func color(_ hex: String?) -> UIColor? {
        guard var raw = hex?.trimmingCharacters(in: .whitespaces), !raw.isEmpty else { return nil }
        if raw.hasPrefix("#") { raw.removeFirst() }
        if raw.count == 3 { raw = raw.map { "\($0)\($0)" }.joined() }
        guard raw.count == 6 || raw.count == 8, let value = UInt64(raw, radix: 16) else { return nil }

        let hasAlpha = raw.count == 8
        let r = CGFloat((value >> (hasAlpha ? 24 : 16)) & 0xFF) / 255
        let g = CGFloat((value >> (hasAlpha ? 16 : 8)) & 0xFF) / 255
        let b = CGFloat((value >> (hasAlpha ? 8 : 0)) & 0xFF) / 255
        let a = hasAlpha ? CGFloat(value & 0xFF) / 255 : 1

        return UIColor(red: r, green: g, blue: b, alpha: a)
    }
}

struct Scene3dChrome: Equatable {
    let background: UIColor?
    let camera: SCNVector3
    let target: SCNVector3
    let fieldOfView: CGFloat
    let lights: [Scene3dLight]

    static func == (a: Scene3dChrome, b: Scene3dChrome) -> Bool {
        a.background == b.background
            && SCNVector3EqualToVector3(a.camera, b.camera)
            && SCNVector3EqualToVector3(a.target, b.target)
            && a.fieldOfView == b.fieldOfView
            && a.lights == b.lights
    }
}

struct Scene3dLight: Equatable {
    let type: String
    let intensity: CGFloat
    let color: UIColor
    let x: Float
    let y: Float
    let z: Float
}

struct Scene3dDocument {
    let chrome: Scene3dChrome
    let nodes: [Scene3dNode]

    static func parse(_ json: String) -> Scene3dDocument? {
        guard
            let data = json.data(using: .utf8),
            let root = (try? JSONSerialization.jsonObject(with: data)) as? [String: Any]
        else { return nil }

        guard Int(Scene3dNode.number(root["v"], 0)) == Scene3dWire.supportedVersion else {
            NSLog("[scene3d] wire version \(root["v"] ?? "?") is not supported by this renderer")
            return nil
        }

        let cam = root["cam"] as? [String: Any] ?? [:]
        let lights = (root["lit"] as? [[String: Any]] ?? []).map { light in
            Scene3dLight(
                type: (light["t"] as? String) ?? "directional",
                intensity: CGFloat(Scene3dNode.number(light["i"], 50000)),
                color: Scene3dNode.color(light["c"] as? String) ?? .white,
                x: Scene3dNode.number(light["x"], 0),
                y: Scene3dNode.number(light["y"], 0),
                z: Scene3dNode.number(light["z"], 0)
            )
        }

        return Scene3dDocument(
            chrome: Scene3dChrome(
                background: Scene3dNode.color(root["bg"] as? String),
                camera: SCNVector3(Scene3dNode.number(cam["x"], 0), Scene3dNode.number(cam["y"], 2), Scene3dNode.number(cam["z"], 8)),
                target: SCNVector3(Scene3dNode.number(cam["tx"], 0), Scene3dNode.number(cam["ty"], 0), Scene3dNode.number(cam["tz"], 0)),
                fieldOfView: CGFloat(Scene3dNode.number(cam["fov"], 60)),
                lights: lights
            ),
            nodes: (root["n"] as? [[String: Any]] ?? []).compactMap(Scene3dNode.parse)
        )
    }
}
