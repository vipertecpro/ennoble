<?php

namespace App\NativeUI\Feedback;

use Native\Mobile\Facades\Dialog;

final class ToastService
{
    /**
     * Show a native toast with a text cue for its semantic type.
     */
    public function show(
        string $message,
        ToastType $type = ToastType::Information,
        string $duration = 'short',
    ): void {
        Dialog::toast("{$type->value}: {$message}", $duration);
    }
}
