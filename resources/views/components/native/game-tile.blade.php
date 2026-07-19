@props([
    'game',
    'motionDuration' => 0,
])

{{-- Compact game tile: illustration, title, best score. Two sit side by side
     so several games are visible at a glance; tapping opens the detail. --}}
<native:pressable
    class="flex-1 items-center gap-3 rounded-2xl bg-theme-surface shadow-sm p-4"
    :press-scale="0.98"
    a11y-label="{{ $game['title'] }}"
    a11y-hint="Opens {{ $game['title'] }} details"
    @press="openGame('{{ $game['slug'] }}')"
>
    <x-native.game-illustration :slug="$game['slug']" :motion-duration="$motionDuration" />

    <native:column class="w-full items-center gap-1">
        <native:text class="w-full text-[16] font-semibold text-center text-theme-primary-text">{{ $game['title'] }}</native:text>
        <native:text class="w-full text-[12] text-center text-theme-muted-text">
            {{ ($game['best_score'] ?? null) === null ? 'Not played yet' : 'Best '.number_format($game['best_score']) }}
        </native:text>
    </native:column>
</native:pressable>
