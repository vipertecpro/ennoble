<?php

namespace App\Enums;

enum GameStatus: string
{
    case Playable = 'playable';
    case ComingSoon = 'coming_soon';
}
