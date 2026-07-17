@props([
    'greeting',
    'displayName',
    'message',
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-2"
    :translate-y="$motionDuration > 0 ? -2 : 0"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $greeting }}, {{ $displayName }}"
>
    <native:text class="text-sm font-semibold text-theme-primary">{{ $greeting }}</native:text>
    <native:text class="text-3xl font-bold leading-tight text-theme-on-background">
        {{ $displayName }}.
    </native:text>
    <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
        {{ $message }}
    </native:text>
</native:column>
