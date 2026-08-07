<?php

namespace Vipertecpro\Scene3d\Scene;

use InvalidArgumentException;

/**
 * A complete scene description, ready to hand to `<native:scene-3d>`.
 *
 * WHAT THIS IS FOR. PHP cannot drive a render loop — the poll floor is ~250ms
 * — so it never describes frames. It describes STATE, plus where that state is
 * heading, and the renderer interpolates at its own framerate. A node given a
 * `spin` or `play` keeps moving with no further contact from PHP.
 *
 * WHY IT IS IMMUTABLE. Every builder call returns a new Scene, so a game can
 * hold a base scene and derive per-frame variants without the risk of
 * accidentally mutating shared state — the bug that would otherwise show up as
 * objects teleporting between waves.
 *
 * THE WIRE FORMAT IS VERSIONED and uses short keys. A scene of a few hundred
 * nodes is encoded on every render that touches it, so the format optimises
 * for size: defaults are omitted entirely rather than sent as zeroes.
 */
final class Scene
{
    /** Bumped when the wire format changes shape, so a renderer can refuse a payload it cannot read. */
    public const WIRE_VERSION = 1;

    /**
     * @param  array<string, Node>  $nodes
     * @param  list<Light>  $lights
     */
    private function __construct(
        private readonly array $nodes = [],
        private readonly Camera $camera = new Camera,
        private readonly array $lights = [],
        private readonly ?string $background = null,
        private readonly ?string $environment = null,
    ) {}

    public static function make(): self
    {
        // A scene with no light renders black, which reads as "the plugin is
        // broken". Defaulting to a key light means the simplest possible scene
        // shows something.
        return new self(lights: [Light::key(), Light::ambient()]);
    }

    /**
     * Add or replace nodes. Keyed by id, so re-adding a node with the same id
     * updates it in place rather than duplicating it.
     */
    public function add(Node ...$nodes): self
    {
        $merged = $this->nodes;

        foreach ($nodes as $node) {
            $merged[$node->id] = $node;
        }

        return $this->with(nodes: $merged);
    }

    public function remove(string ...$ids): self
    {
        $remaining = $this->nodes;

        foreach ($ids as $id) {
            unset($remaining[$id]);
        }

        return $this->with(nodes: $remaining);
    }

    public function node(string $id): ?Node
    {
        return $this->nodes[$id] ?? null;
    }

    /** @return list<Node> */
    public function nodes(): array
    {
        return array_values($this->nodes);
    }

    public function camera(Camera $camera): self
    {
        return $this->with(camera: $camera);
    }

    public function lights(Light ...$lights): self
    {
        return $this->with(lights: array_values($lights));
    }

    public function background(string $hex): self
    {
        return $this->with(background: $hex);
    }

    /**
     * An IBL environment (.ktx) for image-based lighting. This is what makes
     * PBR materials look like anything other than flat plastic, so it is worth
     * shipping one even for simple scenes.
     */
    public function environment(string $path): self
    {
        if (! str_ends_with(strtolower($path), '.ktx')) {
            throw new InvalidArgumentException("Environment [{$path}] must be a .ktx IBL.");
        }

        return $this->with(environment: $path);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'v' => self::WIRE_VERSION,
            'cam' => $this->camera->toArray(),
            'lit' => array_map(static fn (Light $light): array => $light->toArray(), $this->lights),
            'bg' => $this->background,
            'env' => $this->environment,
            'n' => array_map(static fn (Node $node): array => $node->toArray(), array_values($this->nodes)),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, Node>|null  $nodes
     * @param  list<Light>|null  $lights
     */
    private function with(
        ?array $nodes = null,
        ?Camera $camera = null,
        ?array $lights = null,
        ?string $background = null,
        ?string $environment = null,
    ): self {
        return new self(
            nodes: $nodes ?? $this->nodes,
            camera: $camera ?? $this->camera,
            lights: $lights ?? $this->lights,
            background: $background ?? $this->background,
            environment: $environment ?? $this->environment,
        );
    }
}
