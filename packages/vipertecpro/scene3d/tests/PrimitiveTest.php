<?php

use Vipertecpro\Scene3d\Primitives\GltfWriter;
use Vipertecpro\Scene3d\Primitives\PrimitiveFactory;
use Vipertecpro\Scene3d\Scene\Shapes;

beforeEach(function () {
    $this->factory = new PrimitiveFactory;
    $this->writer = new GltfWriter;
});

test('every declared shape has a builder', function () {
    // Shapes::ALL is what the scene API validates against, so a shape that
    // passes validation and then has no geometry would fail only on device.
    foreach (Shapes::ALL as $shape) {
        $mesh = $this->factory->make($shape);

        expect($mesh->vertexCount())->toBeGreaterThan(2)
            ->and($mesh->triangleCount())->toBeGreaterThan(0);
    }
});

test('positions and normals are parallel arrays', function () {
    foreach (Shapes::ALL as $shape) {
        $mesh = $this->factory->make($shape);

        expect(count($mesh->normals))->toBe(count($mesh->positions), "[{$shape}] normals do not match positions");
    }
});

test('every index refers to a vertex that exists', function () {
    // An out-of-range index is undefined behaviour on the GPU — it can render
    // fine on one driver and corrupt memory on another.
    foreach (Shapes::ALL as $shape) {
        $mesh = $this->factory->make($shape);
        $vertices = $mesh->vertexCount();

        expect(max($mesh->indices))->toBeLessThan($vertices, "[{$shape}] has an out-of-range index")
            ->and(min($mesh->indices))->toBeGreaterThanOrEqual(0);
    }
});

test('indices are whole triangles', function () {
    foreach (Shapes::ALL as $shape) {
        expect(count($this->factory->make($shape)->indices) % 3)->toBe(0, "[{$shape}] has a partial triangle");
    }
});

test('vertex counts stay inside the 16-bit index limit', function () {
    // The writer packs indices as UNSIGNED_SHORT; a mesh past 65535 vertices
    // would silently wrap and render as garbage.
    foreach (Shapes::ALL as $shape) {
        expect($this->factory->make($shape)->vertexCount())->toBeLessThan(65536);
    }
});

test('normals are unit length, or lighting is wrong', function () {
    foreach (Shapes::ALL as $shape) {
        $normals = $this->factory->make($shape)->normals;

        for ($i = 0; $i < count($normals); $i += 3) {
            $length = sqrt($normals[$i] ** 2 + $normals[$i + 1] ** 2 + $normals[$i + 2] ** 2);

            expect($length)->toBeGreaterThan(0.99, "[{$shape}] has a non-unit normal")
                ->and($length)->toBeLessThan(1.01);
        }
    }
});

test('primitives are unit-ish and centred, so scale() means one thing', function () {
    foreach (Shapes::ALL as $shape) {
        $positions = $this->factory->make($shape)->positions;
        $extent = max(array_map('abs', $positions));

        expect($extent)->toBeLessThanOrEqual(0.75, "[{$shape}] is larger than a unit");
    }
});

test('the encoded document is structurally valid glTF 2.0', function () {
    $gltf = $this->writer->encode($this->factory->make(Shapes::BOX), 'box');

    expect($gltf['asset']['version'])->toBe('2.0')
        ->and($gltf['meshes'][0]['primitives'][0]['attributes'])->toHaveKeys(['POSITION', 'NORMAL'])
        // The spec REQUIRES min/max on POSITION; loaders use them for culling,
        // so omitting them fails on device rather than at load.
        ->and($gltf['accessors'][0])->toHaveKeys(['min', 'max'])
        ->and($gltf['accessors'][0]['min'])->toHaveCount(3);
});

test('buffer views describe exactly the bytes that were packed', function () {
    $mesh = $this->factory->make(Shapes::SPHERE);
    $gltf = $this->writer->encode($mesh, 'sphere');

    [$position, $normal, $index] = $gltf['bufferViews'];

    expect($position['byteLength'])->toBe($mesh->vertexCount() * 3 * 4)
        ->and($normal['byteLength'])->toBe($mesh->vertexCount() * 3 * 4)
        ->and($index['byteLength'])->toBe(count($mesh->indices) * 2);
});

test('index data is four-byte aligned as the spec requires', function () {
    foreach (Shapes::ALL as $shape) {
        $gltf = $this->writer->encode($this->factory->make($shape), $shape);

        // Unsigned shorts are only 2-byte aligned, so an odd-sized preceding
        // block has to be padded or strict loaders reject the asset.
        expect($gltf['bufferViews'][2]['byteOffset'] % 4)->toBe(0, "[{$shape}] index view is misaligned");
    }
});

test('the embedded buffer decodes to the declared length', function () {
    $gltf = $this->writer->encode($this->factory->make(Shapes::TORUS), 'torus');
    $uri = $gltf['buffers'][0]['uri'];

    expect($uri)->toStartWith('data:application/octet-stream;base64,');

    $decoded = base64_decode(substr($uri, strlen('data:application/octet-stream;base64,')), true);

    expect(strlen($decoded))->toBe($gltf['buffers'][0]['byteLength']);
});

test('positions round-trip through the packed buffer intact', function () {
    $mesh = $this->factory->make(Shapes::BOX);
    $gltf = $this->writer->encode($mesh, 'box');

    $buffer = base64_decode(substr($gltf['buffers'][0]['uri'], strlen('data:application/octet-stream;base64,')), true);
    $view = $gltf['bufferViews'][0];
    $unpacked = array_values(unpack('g*', substr($buffer, $view['byteOffset'], $view['byteLength'])));

    foreach ($mesh->positions as $i => $expected) {
        expect($unpacked[$i])->toBeGreaterThan($expected - 0.0001)
            ->and($unpacked[$i])->toBeLessThan($expected + 0.0001);
    }
});

test('the GLB container is byte-correct, since a bad one crashes the renderer natively', function () {
    // Verified against the container spec rather than "it loaded once": a GLB
    // whose header length or chunk padding is wrong is read as garbage by the
    // native loader, and the failure surfaces as a SIGSEGV in Filament with a
    // stack that names nothing of ours. It is only checkable here.
    $writer = new GltfWriter;
    $factory = new PrimitiveFactory;

    foreach (Shapes::ALL as $shape) {
        $glb = $writer->toGlb($factory->make($shape), $shape);

        ['magic' => $magic, 'version' => $version, 'length' => $length] =
            unpack('a4magic/Vversion/Vlength', substr($glb, 0, 12));

        expect($magic)->toBe('glTF', "[{$shape}] is not a GLB.")
            ->and($version)->toBe(2)
            ->and($length)->toBe(strlen($glb), "[{$shape}] header length disagrees with the file.");

        $offset = 12;
        $chunks = [];

        while ($offset < strlen($glb)) {
            ['len' => $len, 'type' => $type] = unpack('Vlen/a4type', substr($glb, $offset, 8));

            expect($len % 4)->toBe(0, "[{$shape}] chunk {$type} is not 4-byte aligned.");

            $chunks[trim($type)] = substr($glb, $offset + 8, $len);
            $offset += 8 + $len;
        }

        expect($offset)->toBe(strlen($glb), "[{$shape}] chunks overrun the file.")
            ->and(array_key_first($chunks))->toBe('JSON', "[{$shape}] JSON must be the first chunk.");

        $document = json_decode($chunks['JSON'], true, flags: JSON_THROW_ON_ERROR);

        // The whole reason for moving off .gltf: a GLB buffer has no uri, so
        // there is no resource for the loader to fail to resolve.
        expect($document['buffers'][0])->not->toHaveKey('uri')
            ->and($document['buffers'][0]['byteLength'])->toBeLessThanOrEqual(strlen($chunks['BIN']));
    }
});

test('every triangle faces outward, or the shape renders as a black silhouette', function () {
    // glTF's front face is counter-clockwise and renderers cull back faces, so
    // an inverted loop shows a shape's interior — lit from behind, drawn flat
    // black. It is invisible in the generator and unmistakable on a device.
    $factory = new PrimitiveFactory;

    foreach (Shapes::ALL as $shape) {
        $mesh = $factory->make($shape);
        $inverted = 0;

        for ($i = 0; $i < count($mesh->indices); $i += 3) {
            [$a, $b, $c] = [$mesh->indices[$i], $mesh->indices[$i + 1], $mesh->indices[$i + 2]];

            $ux = $mesh->positions[$b * 3] - $mesh->positions[$a * 3];
            $uy = $mesh->positions[$b * 3 + 1] - $mesh->positions[$a * 3 + 1];
            $uz = $mesh->positions[$b * 3 + 2] - $mesh->positions[$a * 3 + 2];
            $vx = $mesh->positions[$c * 3] - $mesh->positions[$a * 3];
            $vy = $mesh->positions[$c * 3 + 1] - $mesh->positions[$a * 3 + 1];
            $vz = $mesh->positions[$c * 3 + 2] - $mesh->positions[$a * 3 + 2];

            $fx = $uy * $vz - $uz * $vy;
            $fy = $uz * $vx - $ux * $vz;
            $fz = $ux * $vy - $uy * $vx;

            $nx = ($mesh->normals[$a * 3] + $mesh->normals[$b * 3] + $mesh->normals[$c * 3]) / 3;
            $ny = ($mesh->normals[$a * 3 + 1] + $mesh->normals[$b * 3 + 1] + $mesh->normals[$c * 3 + 1]) / 3;
            $nz = ($mesh->normals[$a * 3 + 2] + $mesh->normals[$b * 3 + 2] + $mesh->normals[$c * 3 + 2]) / 3;

            if ($fx * $nx + $fy * $ny + $fz * $nz < 0.0) {
                $inverted++;
            }
        }

        expect($inverted)->toBe(0, "[{$shape}] has {$inverted} inside-out triangles.");
    }
});
