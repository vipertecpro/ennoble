<?php

namespace App\NativeUI\Feedback;

enum ToastType: string
{
    case Success = 'Success';
    case Error = 'Error';
    case Warning = 'Warning';
    case Information = 'Information';
}
