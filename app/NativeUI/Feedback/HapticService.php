<?php

namespace App\NativeUI\Feedback;

use App\Domain\Profile\ProfileService;
use Native\Mobile\Facades\Device;

final class HapticService
{
    public function __construct(private readonly ProfileService $profiles) {}

    /**
     * Emit optional semantic feedback through the installed generic vibration API.
     */
    public function trigger(HapticFeedback $feedback): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return Device::vibrate();
    }

    /**
     * Determine whether the local profile allows haptic feedback.
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->profiles->current()?->setting?->haptics_enabled ?? true);
    }
}
