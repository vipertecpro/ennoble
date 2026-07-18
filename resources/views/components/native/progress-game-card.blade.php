@props([
    'name',
    'best',
    'sessions',
    'accuracy',
    'motionDuration' => 0,
])

<native:column class="w-80 items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
<native:column class="w-72 gap-3">
    <native:row class="items-center justify-between">
        <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $name }}</native:text>
        <native:column class="items-end gap-1">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">BEST</native:text>
            <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">{{ $best }}</native:text>
        </native:column>
    </native:row>

    <native:text class="text-[15] text-theme-muted-text">{{ $sessions }} · {{ $accuracy }}</native:text>
</native:column>
</native:column>
