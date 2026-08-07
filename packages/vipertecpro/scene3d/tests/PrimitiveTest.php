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
