@props([
    'date' => '',
    'greeting',
    'displayName',
    'initial' => '',
    'message' => null,
    'motionDuration' => 0,
])

{{-- Cortex dashboard header — a quiet date line, the greeting + name as the
     display heading, and a neutral avatar initial on the right. The avatar
     stays off-accent so the screen's single lime moment is the session CTA. --}}
<native:row
    class="w-full items-center justify-between gap-4"
    :translate-y="$motionDuration > 0 ? -2 : 0"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $greeting }}, {{ $displayName }}"
>
    <native:column class="flex-1 gap-1">
        @if ($date)
            <native:text class="text-[13] text-theme-muted-text">{{ $date }}</native:text>
        @endif
        <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">
            {{ $greeting }}, {{ $displayName }}
        </native:text>
        @if ($message)
            <native:text class="text-[14] leading-relaxed text-theme-secondary-text">{{ $message }}</native:text>
        @endif
    </native:column>
    <native:column class="w-10 h-10 items-center justify-center rounded-full bg-theme-secondary-surface">
        <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $initial }}</native:text>
    </native:column>
</native:row>
