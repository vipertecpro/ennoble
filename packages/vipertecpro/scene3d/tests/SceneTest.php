<?php

use Vipertecpro\Scene3d\Scene\Camera;
use Vipertecpro\Scene3d\Scene\Light;
use Vipertecpro\Scene3d\Scene\Material;
use Vipertecpro\Scene3d\Scene\Node;
use Vipertecpro\Scene3d\Scene\Scene;
use Vipertecpro\Scene3d\Scene\Shapes;

test('a scene lights itself so the simplest case is never a black screen', function () {
    $wire = Scene::make()->toArray();

    expect($wire['lit'])->toHaveCount(2)
        ->and($wire['v'])->toBe(Scene::WIRE_VERSION);
});

test('the scene is immutable, so derived frames cannot corrupt a shared base', function () {
    $base = Scene::make()->add(Node::shape('a', 'box'));
    $derived = $base->add(Node::shape('b', 'sphere'));

    // Without this, a game holding a base scene and deriving per-wave variants
    // would see objects teleport between waves.
    expect($base->nodes())->toHaveCount(1)
        ->and($derived->nodes())->toHaveCount(2);
});

test('adding a node with an existing id replaces it rather than duplicating', function () {
    $scene = Scene::make()
        ->add(Node::shape('hero', 'box'))
        ->add(Node::shape('hero', 'sphere'));

    expect($scene->nodes())->toHaveCount(1)
        ->and($scene->node('hero')->shape)->toBe('sphere');
});

test('every mutation bumps the revision the renderer diffs on', function () {
    $node = Node::shape('a', 'box');
    $moved = $node->at(1, 2, 3);
    $coloured = $moved->color('#FF0000');

    // The revision is what lets the renderer skip untouched nodes in O(1)
    // instead of deep-comparing every descriptor each frame.
    expect($node->revision)->toBe(1)
        ->and($moved->revision)->toBe(2)
        ->and($coloured->revision)->toBe(3);
});

test('defaults are omitted from the wire rather than sent as zeroes', function () {
    $wire = Node::shape('a', 'box')->toArray();

    // A scene of hundreds of nodes is encoded on every render that touches it;
    // absent bytes are the cheapest optimisation available.
    expect($wire)->toBe(['id' => 'a', 'r' => 1, 'g' => 'box'])
        ->and($wire)->not->toHaveKeys(['x', 'y', 'z', 's', 'o']);
});

test('a positioned, scaled node ships only what actually changed', function () {
    $wire = Node::shape('a', 'box')->at(1.5, 0, -3)->scale(2)->toArray();

    expect($wire['x'])->toBe(1.5)
        ->and($wire['z'])->toBe(-3.0)
        ->and($wire['s'])->toBe(2.0)
        // y is still zero, so it is not on the wire at all.
        ->and($wire)->not->toHaveKey('y');
});

test('models must be glTF, the one format both renderers load natively', function () {
    expect(fn () => Node::model('hero', 'hero.fbx'))
        ->toThrow(InvalidArgumentException::class, 'must be .gltf or .glb');

    expect(Node::model('hero', 'characters/hero.glb')->toArray()['m'])
        ->toBe('characters/hero.glb');
});

test('an unknown primitive fails loudly at author time, not silently on device', function () {
    expect(fn () => Node::shape('a', 'dodecahedron'))
        ->toThrow(InvalidArgumentException::class, 'Unknown shape');
});

test('animation, spin and movement all survive onto the wire', function () {
    $wire = Node::model('hero', 'hero.glb')
        ->play('walk', loop: true, speed: 1.5)
        ->spin('y', 3.0)
        ->moveTo(0, 0, -8, 2.5)
        ->tappable()
        ->toArray();

    expect($wire['clip']['n'])->toBe('walk')
        ->and($wire['clip']['sp'])->toBe(1.5)
        ->and($wire['spin']['s'])->toBe(3.0)
        ->and($wire['move']['z'])->toBe(-8.0)
        ->and($wire['tap'])->toBe(1);
});

test('opacity is clamped rather than trusted', function () {
    expect(Node::shape('a', 'box')->opacity(4.2)->opacity)->toBe(1.0)
        ->and(Node::shape('a', 'box')->opacity(-1)->opacity)->toBe(0.0);
});

test('materials express what a PBR renderer can actually honour', function () {
    expect(Material::metal('#C0C0C0')->toArray())
        ->toBe(['c' => '#C0C0C0', 'me' => 1.0, 'ro' => 0.25])
        ->and(Material::glowing('#10B981', 2.0)->toArray()['em'])->toBe(2.0);
});

test('the camera and lights are not nodes, so a scene update cannot delete them', function () {
    $scene = Scene::make()
        ->camera((new Camera)->at(0, 2, 9)->lookAt(0, 0, -4))
        ->lights(Light::point(2, 3, 1))
        ->add(Node::shape('a', 'box'));

    $wire = $scene->toArray();

    // Clearing every node must leave the viewport lit and viewable.
    $emptied = $scene->remove('a')->toArray();

    expect($wire['cam']['z'])->toBe(9.0)
        ->and($wire['cam']['tz'])->toBe(-4.0)
        ->and($emptied)->not->toHaveKey('n')
        ->and($emptied['cam'])->not->toBeEmpty()
        ->and($emptied['lit'])->toHaveCount(1);
});

test('an environment must be a ktx IBL', function () {
    expect(fn () => Scene::make()->environment('studio.hdr'))
        ->toThrow(InvalidArgumentException::class, 'must be a .ktx IBL');
});

test('the encoded scene is valid, versioned json', function () {
    $json = Scene::make()->add(Node::shape('a', 'box')->color('#38BDF8'))->toJson();
    $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    expect($decoded['v'])->toBe(Scene::WIRE_VERSION)
        ->and($decoded['n'][0]['mat']['c'])->toBe('#38BDF8');
});

it('distinguishes a zero roughness from an omitted one', function () {
    // The wire omits defaults to stay small, but "falsy" and "default" are not
    // the same thing: a mirror finish is roughness 0.0, and filtering falsy
    // values sent it as absent, which the renderer reads back as the 0.5
    // default. The surface silently stopped being reflective.
    $mirror = new Material(color: '#FFFFFF', roughness: 0.0);

    expect($mirror->toArray())->toHaveKey('ro')
        ->and($mirror->toArray()['ro'])->toBe(0.0);

    // The actual default still goes unsent.
    expect((new Material(color: '#FFFFFF'))->toArray())->not->toHaveKey('ro');

    // Metallic and emissive default to zero, so zero IS absent for them.
    expect((new Material(color: '#FFFFFF'))->toArray())
        ->not->toHaveKey('me')
        ->not->toHaveKey('em');
});

it('keeps uniform scale on one wire key and spells out non-uniform scale', function () {
    // Uniform is the common case and stays compact...
    $uniform = Node::shape('a', Shapes::BOX)->scale(2.0)->toArray();

    expect($uniform)->toHaveKey('s')
        ->and($uniform)->not->toHaveKey('sy')
        ->and($uniform['s'])->toBe(2.0);

    // ...and non-uniform sends all three, INCLUDING any that happen to be 1.0.
    // With per-axis scale there is no "absent means same as the others"
    // shorthand left, so omitting a 1.0 would be read back as the x scale.
    $floor = Node::shape('floor', Shapes::BOX)->size(40.0, 1.0, 6.0)->toArray();

    expect($floor['s'])->toBe(40.0)
        ->and($floor['sy'])->toBe(1.0)
        ->and($floor['sz'])->toBe(6.0);
});

it('lets size() and scale() override one another rather than combining', function () {
    // Both write the same slot; the last call wins. Combining them would make
    // the result depend on call order in a way nobody would predict.
    expect(Node::shape('a', Shapes::BOX)->size(4.0, 1.0, 2.0)->scale(3.0)->toArray())
        ->not->toHaveKey('sy');

    expect(Node::shape('b', Shapes::BOX)->scale(3.0)->size(4.0, 1.0, 2.0)->toArray()['sy'])
        ->toBe(1.0);
});
