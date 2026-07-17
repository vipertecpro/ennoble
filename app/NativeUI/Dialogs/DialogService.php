<?php

namespace App\NativeUI\Dialogs;

use Native\Mobile\Facades\Dialog;
use Native\Mobile\PendingAlert;

final class DialogService
{
    /**
     * Create a native alert that callers may attach a button callback to.
     */
    public function alert(string $title, string $message, string $button = 'OK'): PendingAlert
    {
        return Dialog::alert($title, $message, [$button]);
    }

    /**
     * Create a native confirmation with explicit cancel and destructive actions.
     */
    public function confirm(
        string $title,
        string $message,
        string $confirmLabel = 'Confirm',
        string $cancelLabel = 'Cancel',
    ): PendingAlert {
        return Dialog::alert($title, $message, [
            ['label' => $cancelLabel, 'style' => 'cancel'],
            ['label' => $confirmLabel, 'style' => 'destructive'],
        ]);
    }
}
