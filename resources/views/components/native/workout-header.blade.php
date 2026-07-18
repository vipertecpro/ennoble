@props([
    'eyebrow',
    'title',
    'subtitle' => null,
    'motionDuration' => 0,
])

<native:column
    class="gap-3"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $eyebrow }}. {{ $title }}{{ $subtitle ? '. '.$subtitle : '' }}"
>
    <native:column class="gap-2">
        <native:text class="text-xs font-semibold uppercase text-theme-accent">{{ $eyebrow }}</native:text>
        <native:text class="text-3xl font-bold leading-tight text-theme-primary-text">{{ $title }}</native:text>
        @if ($subtitle)
            <native:text class="text-base leading-relaxed text-theme-secondary-text">{{ $subtitle }}</native:text>
        @endif
    </native:column>
</native:column>
