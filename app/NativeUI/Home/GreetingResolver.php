<?php

namespace App\NativeUI\Home;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

final class GreetingResolver
{
    /**
     * Resolve a contextual greeting from the device-local hour.
     */
    public function greeting(CarbonInterface $time): string
    {
        return match (true) {
            $time->hour >= 5 && $time->hour < 12 => 'Good Morning',
            $time->hour >= 12 && $time->hour < 17 => 'Good Afternoon',
            default => 'Good Evening',
        };
    }

    /**
     * Normalize the optional local display name for a friendly greeting.
     */
    public function displayName(?string $displayName): string
    {
        $normalizedDisplayName = Str::squish($displayName ?? '');

        return $normalizedDisplayName === '' ? 'friend' : $normalizedDisplayName;
    }
}
