<?php

namespace App\NativeUI\Dialogs;

trait InteractsWithDialogs
{
    public bool $dialogVisible = false;

    public bool $bottomSheetVisible = false;

    /**
     * Present reusable rich alert content.
     */
    public function showDialog(): void
    {
        $this->dialogVisible = true;
    }

    /**
     * Dismiss reusable rich alert content.
     */
    public function dismissDialog(): void
    {
        $this->dialogVisible = false;
    }

    /**
     * Present reusable bottom-sheet content.
     */
    public function showBottomSheet(): void
    {
        $this->bottomSheetVisible = true;
    }

    /**
     * Dismiss reusable bottom-sheet content.
     */
    public function dismissBottomSheet(): void
    {
        $this->bottomSheetVisible = false;
    }
}
