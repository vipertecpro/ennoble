<?php

namespace Vipertecpro\Scene3d\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed execute(array $options = [])
 * @method static object|null getStatus()
 *
 * @see \Vipertecpro\Scene3d\Scene3d
 */
class Scene3d extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Vipertecpro\Scene3d\Scene3d::class;
    }
}
