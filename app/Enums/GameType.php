<?php

namespace App\Enums;

enum GameType: string
{
    case SignalShift = 'signal_shift';
    case ClearThought = 'clear_thought';
    case WordMatch = 'word_match';
    case QuickMath = 'quick_math';
}
