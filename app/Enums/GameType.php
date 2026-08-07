<?php

namespace App\Enums;

use Native\Mobile\Platform;

enum GameType: string
{
    case WordMatch = 'word_match';
    case QuickMath = 'quick_math';
    case Recall = 'recall';
    case Flow = 'flow';
    case Signal = 'signal';
    case Vertex = 'vertex';
    case Axis = 'axis';

    /**
     * Whether this game's renderer exists on the device we are running on.
     *
     * Axis draws through the scene3d plugin, which currently ships an Android
     * renderer only; listing it on iOS would offer a game whose viewport
     * cannot draw. Delete this the moment the iOS renderer lands.
     *
     * Hidden only where the platform is KNOWN to be iOS. Off-device —
     * tests, the web preview — detection returns null, and treating unknown
     * as "hide" would make the game vanish from every test and from local
     * development, which is a worse failure than showing it.
     */
    public function rendersOnThisPlatform(): bool
    {
        if ($this !== self::Axis) {
            return true;
        }

        return Platform::current() !== Platform::IOS;
    }
}
