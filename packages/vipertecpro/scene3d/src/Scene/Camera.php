<?php

namespace Vipertecpro\Scene3d\Scene;

/**
 * The viewpoint. Lives outside the node graph on purpose: it is viewport
 * furniture, not scene content, so a scene update can never accidentally
 * delete the camera and leave a black screen.
 */
final class Camera
{
    public function __construct(
        public readonly float $x = 0.0,
        public readonly float $y = 0.0,
        public readonly float $z = 6.0,
        public readonly float $fieldOfView = 60.0,
        public readonly float $lookAtX = 0.0,
        public readonly float $lookAtY = 0.0,
        public readonly float $lookAtZ = 0.0,
    ) {}

    public function at(float $x, float $y, float $z): self
    {
        return new self($x, $y, $z, $this->fieldOfView, $this->lookAtX, $this->lookAtY, $this->lookAtZ);
    }

    public function lookAt(float $x, float $y, float $z): self
    {
        return new self($this->x, $this->y, $this->z, $this->fieldOfView, $x, $y, $z);
    }

    public function fieldOfView(float $degrees): self
    {
        return new self($this->x, $this->y, $this->z, $degrees, $this->lookAtX, $this->lookAtY, $this->lookAtZ);
    }

    /**
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'x' => $this->x, 'y' => $this->y, 'z' => $this->z,
            'fov' => $this->fieldOfView,
            'tx' => $this->lookAtX, 'ty' => $this->lookAtY, 'tz' => $this->lookAtZ,
        ];
    }
}
