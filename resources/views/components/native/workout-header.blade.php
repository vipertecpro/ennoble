@props([
    'eyebrow',
    'title',
    'subtitle' => null,
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-3"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $eyebrow }}. {{ $title }}{{ $subtitle ? '. '.$subtitle : '' }}"
>
    <native:row class="w-full items-start gap-4">
        <native:column class="flex-1 gap-2">
            <native:text class="text-xs font-semibold uppercase text-theme-primary">{{ $eyebrow }}</native:text>
            <native:text class="text-3xl font-bold leading-tight text-theme-on-background">{{ $title }}</native:text>
            @if ($subtitle)
                <native:text class="text-base leading-relaxed text-theme-on-surface-variant">{{ $subtitle }}</native:text>
            @endif
        </native:column>

        @if (isset($action))
            {{ $action }}
        @endif
    </native:row>
</native:column>
