<?php

namespace App\NativeUI\Screens;

enum ShellState: string
{
    case Content = 'content';
    case Loading = 'loading';
    case Empty = 'empty';
    case Error = 'error';
}
