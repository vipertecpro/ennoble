<?php

namespace App\Enums;

enum SessionStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
    case Invalid = 'invalid';
}
