<?php

namespace App\Enums;

enum RoundOutcome: string
{
    case Correct = 'correct';
    case Incorrect = 'incorrect';
    case Missed = 'missed';
}
