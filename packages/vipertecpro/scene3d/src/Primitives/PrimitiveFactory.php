<?php

namespace Vipertecpro\Scene3d\Primitives;

use InvalidArgumentException;
use Vipertecpro\Scene3d\Scene\Shapes;

/**
 * Builds the plugin's built-in primitives as triangle meshes.
 *
 * WHY PRIMITIVES ARE glTF AND NOT VERTEX BUFFERS. Filament shades everything
 * through a compiled material, and compiling one needs `matc` — a build step
 * this plugin deliberately does not require. gltfio sidesteps that with its
 * ubershader, but only for glTF-loaded assets. So the cheapest route to a lit
 * cube is to make the cube a glTF asset like any other model: one loading path
 * for primitives and characters alike, no material compiler, and the same
 * files serve both platforms.
 *
 * Every primitive is built around the origin and sized to roughly one unit, so
 * `scale()` in the scene API means the same thing whichever shape you pick.
 */
final class PrimitiveFactory
{
    public function make(string $shape): Mesh
    {
        $mesh = match ($shape) {
            Shapes::BOX => $this->box(),
            Shapes::PLANE => $this->plane(),
            Shapes::SPHERE => $this->sphere(),
            Shapes::CYLINDER => $this->cylinder(),
            Shapes::CONE => $this->cone(),
            Shapes::TORUS => $this->torus(),
            Shapes::CAPSULE => $this->capsule(),
            default => throw new InvalidArgumentException("No primitive builder for [{$shape}]."),
        };

        return $this->faceOutward($mesh);
    }

    /**
     * Make every triangle wind counter-clockwise when seen from outside.
     *
     * glTF defines the front face as counter-clockwise, and a renderer culls
     * back faces by default. A shape whose loops run the other way is drawn
     * inside-out: you see its interior, lit from behind, so it renders as a
     * flat black silhouette. The sphere, capsule and torus were fully
     * inverted this way and the cylinder and cone half — their caps disagreed
     * with their walls — which is exactly the kind of per-loop mistake that is
     * invisible in the generator and obvious on a screen.
     *
     * Winding is corrected against the VERTEX NORMALS rather than each loop
     * being fixed by hand: the normals are authored analytically and are
     * independently verified to point outward, so they are the more trustworthy
     * of the two. This also means a new primitive cannot get it wrong.
     */
    private function faceOutward(Mesh $mesh): Mesh
    {
        $positions = $mesh->positions;
        $normals = $mesh->normals;
        $indices = $mesh->indices;

        for ($i = 0; $i < count($indices); $i += 3) {
            [$a, $b, $c] = [$indices[$i], $indices[$i + 1], $indices[$i + 2]];

            // Geometric normal of the triangle, by the right-hand rule.
            $ux = $positions[$b * 3] - $positions[$a * 3];
            $uy = $positions[$b * 3 + 1] - $positions[$a * 3 + 1];
            $uz = $positions[$b * 3 + 2] - $positions[$a * 3 + 2];
            $vx = $positions[$c * 3] - $positions[$a * 3];
            $vy = $positions[$c * 3 + 1] - $positions[$a * 3 + 1];
            $vz = $positions[$c * 3 + 2] - $positions[$a * 3 + 2];

            $fx = $uy * $vz - $uz * $vy;
            $fy = $uz * $vx - $ux * $vz;
            $fz = $ux * $vy - $uy * $vx;

            $nx = ($normals[$a * 3] + $normals[$b * 3] + $normals[$c * 3]) / 3;
            $ny = ($normals[$a * 3 + 1] + $normals[$b * 3 + 1] + $normals[$c * 3 + 1]) / 3;
            $nz = ($normals[$a * 3 + 2] + $normals[$b * 3 + 2] + $normals[$c * 3 + 2]) / 3;

            // A degenerate triangle has no meaningful facing; leave it alone.
            if ($fx * $nx + $fy * $ny + $fz * $nz >= 0.0) {
                continue;
            }

            [$indices[$i + 1], $indices[$i + 2]] = [$c, $b];
        }

        return new Mesh($positions, $normals, $indices);
    }

    /**
     * A unit cube. Faces do NOT share vertices: a shared corner would have to
     * average three perpendicular normals and the cube would look inflated.
     */
    private function box(float $size = 0.5): Mesh
    {
        $faces = [
            [[0, 0, 1], [[-1, -1, 1], [1, -1, 1], [1, 1, 1], [-1, 1, 1]]],
            [[0, 0, -1], [[1, -1, -1], [-1, -1, -1], [-1, 1, -1], [1, 1, -1]]],
            [[1, 0, 0], [[1, -1, 1], [1, -1, -1], [1, 1, -1], [1, 1, 1]]],
            [[-1, 0, 0], [[-1, -1, -1], [-1, -1, 1], [-1, 1, 1], [-1, 1, -1]]],
            [[0, 1, 0], [[-1, 1, 1], [1, 1, 1], [1, 1, -1], [-1, 1, -1]]],
            [[0, -1, 0], [[-1, -1, -1], [1, -1, -1], [1, -1, 1], [-1, -1, 1]]],
        ];

        $positions = [];
        $normals = [];
        $indices = [];

        foreach ($faces as [$normal, $corners]) {
            $base = intdiv(count($positions), 3);

            foreach ($corners as $corner) {
                array_push($positions, $corner[0] * $size, $corner[1] * $size, $corner[2] * $size);
                array_push($normals, ...$normal);
            }

            array_push($indices, $base, $base + 1, $base + 2, $base, $base + 2, $base + 3);
        }

        return new Mesh($positions, $normals, $indices);
    }

    private function plane(float $size = 0.5): Mesh
    {
        return new Mesh(
            positions: [-$size, 0, $size, $size, 0, $size, $size, 0, -$size, -$size, 0, -$size],
            normals: [0, 1, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0],
            indices: [0, 1, 2, 0, 2, 3],
        );
    }

    /** A UV sphere. Normals are the unit position, which is free on a sphere. */
    private function sphere(float $radius = 0.5, int $rings = 24, int $segments = 32): Mesh
    {
        $positions = [];
        $normals = [];
        $indices = [];

        for ($ring = 0; $ring <= $rings; $ring++) {
            $phi = M_PI * $ring / $rings;

            for ($segment = 0; $segment <= $segments; $segment++) {
                $theta = 2 * M_PI * $segment / $segments;

                $nx = sin($phi) * cos($theta);
                $ny = cos($phi);
                $nz = sin($phi) * sin($theta);

                array_push($positions, $nx * $radius, $ny * $radius, $nz * $radius);
                array_push($normals, $nx, $ny, $nz);
            }
        }

        $stride = $segments + 1;

        for ($ring = 0; $ring < $rings; $ring++) {
            for ($segment = 0; $segment < $segments; $segment++) {
                $a = $ring * $stride + $segment;
                $b = $a + $stride;

                array_push($indices, $a, $b, $a + 1, $a + 1, $b, $b + 1);
            }
        }

        return new Mesh($positions, $normals, $indices);
    }

    private function cylinder(float $radius = 0.4, float $height = 1.0, int $segments = 32): Mesh
    {
        return $this->tube($radius, $radius, $height, $segments);
    }

    private function cone(float $radius = 0.5, float $height = 1.0, int $segments = 32): Mesh
    {
        return $this->tube($radius, 0.0001, $height, $segments);
    }

    /**
     * Shared body for cylinder and cone — a cone is just a tube whose top
     * radius has collapsed. The top is not exactly zero because a degenerate
     * ring produces zero-area triangles and undefined normals at the tip.
     */
    private function tube(float $bottomRadius, float $topRadius, float $height, int $segments): Mesh
    {
        $positions = [];
        $normals = [];
        $indices = [];
        $half = $height / 2;

        $slope = atan2($bottomRadius - $topRadius, $height);

        for ($ring = 0; $ring <= 1; $ring++) {
            $radius = $ring === 0 ? $bottomRadius : $topRadius;
            $y = $ring === 0 ? -$half : $half;

            for ($segment = 0; $segment <= $segments; $segment++) {
                $theta = 2 * M_PI * $segment / $segments;
                $cos = cos($theta);
                $sin = sin($theta);

                array_push($positions, $cos * $radius, $y, $sin * $radius);
                array_push($normals, $cos * cos($slope), sin($slope), $sin * cos($slope));
            }
        }

        $stride = $segments + 1;

        for ($segment = 0; $segment < $segments; $segment++) {
            $a = $segment;
            $b = $segment + $stride;

            array_push($indices, $a, $b, $a + 1, $a + 1, $b, $b + 1);
        }

        $this->addCap($positions, $normals, $indices, $bottomRadius, -$half, $segments, facingUp: false);
        $this->addCap($positions, $normals, $indices, $topRadius, $half, $segments, facingUp: true);

        return new Mesh($positions, $normals, $indices);
    }

    /**
     * @param  list<float>  $positions
     * @param  list<float>  $normals
     * @param  list<int>  $indices
     */
    private function addCap(array &$positions, array &$normals, array &$indices, float $radius, float $y, int $segments, bool $facingUp): void
    {
        $normal = $facingUp ? 1 : -1;
        $centre = intdiv(count($positions), 3);

        array_push($positions, 0, $y, 0);
        array_push($normals, 0, $normal, 0);

        for ($segment = 0; $segment <= $segments; $segment++) {
            $theta = 2 * M_PI * $segment / $segments;

            array_push($positions, cos($theta) * $radius, $y, sin($theta) * $radius);
            array_push($normals, 0, $normal, 0);
        }

        for ($segment = 0; $segment < $segments; $segment++) {
            $a = $centre + 1 + $segment;
            $b = $a + 1;

            // Winding flips with the cap so both read as front-facing.
            $facingUp
                ? array_push($indices, $centre, $a, $b)
                : array_push($indices, $centre, $b, $a);
        }
    }

    private function torus(float $ringRadius = 0.35, float $pipeRadius = 0.15, int $rings = 32, int $sides = 20): Mesh
    {
        $positions = [];
        $normals = [];
        $indices = [];

        for ($ring = 0; $ring <= $rings; $ring++) {
            $u = 2 * M_PI * $ring / $rings;

            for ($side = 0; $side <= $sides; $side++) {
                $v = 2 * M_PI * $side / $sides;

                $nx = cos($v) * cos($u);
                $ny = sin($v);
                $nz = cos($v) * sin($u);

                array_push(
                    $positions,
                    ($ringRadius + $pipeRadius * cos($v)) * cos($u),
                    $pipeRadius * sin($v),
                    ($ringRadius + $pipeRadius * cos($v)) * sin($u),
                );
                array_push($normals, $nx, $ny, $nz);
            }
        }

        $stride = $sides + 1;

        for ($ring = 0; $ring < $rings; $ring++) {
            for ($side = 0; $side < $sides; $side++) {
                $a = $ring * $stride + $side;
                $b = $a + $stride;

                array_push($indices, $a, $b, $a + 1, $a + 1, $b, $b + 1);
            }
        }

        return new Mesh($positions, $normals, $indices);
    }

    /** A sphere cut at the equator and pulled apart by the cylinder's height. */
    private function capsule(float $radius = 0.3, float $height = 0.6, int $rings = 16, int $segments = 24): Mesh
    {
        $positions = [];
        $normals = [];
        $indices = [];
        $half = $height / 2;

        for ($ring = 0; $ring <= $rings; $ring++) {
            $phi = M_PI * $ring / $rings;
            $ny = cos($phi);

            for ($segment = 0; $segment <= $segments; $segment++) {
                $theta = 2 * M_PI * $segment / $segments;

                $nx = sin($phi) * cos($theta);
                $nz = sin($phi) * sin($theta);

                array_push(
                    $positions,
                    $nx * $radius,
                    $ny * $radius + ($ny >= 0 ? $half : -$half),
                    $nz * $radius,
                );
                array_push($normals, $nx, $ny, $nz);
            }
        }

        $stride = $segments + 1;

        for ($ring = 0; $ring < $rings; $ring++) {
            for ($segment = 0; $segment < $segments; $segment++) {
                $a = $ring * $stride + $segment;
                $b = $a + $stride;

                array_push($indices, $a, $b, $a + 1, $a + 1, $b, $b + 1);
            }
        }

        return new Mesh($positions, $normals, $indices);
    }
}
