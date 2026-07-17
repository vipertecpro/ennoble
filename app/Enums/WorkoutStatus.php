<?php

namespace App\Enums;

enum WorkoutStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
