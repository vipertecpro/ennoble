<?php

namespace App\NativeUI\Tokens;

enum MotionToken: string
{
    case Fast = 'fast';
    case Normal = 'normal';
    case Slow = 'slow';
    case Spring = 'spring';
    case Success = 'success';
    case Error = 'error';
}
