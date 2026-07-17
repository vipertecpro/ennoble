<?php

namespace App\NativeUI\Feedback;

enum HapticFeedback: string
{
    case Success = 'success';
    case Error = 'error';
    case Warning = 'warning';
    case Selection = 'selection';
    case Impact = 'impact';
}
