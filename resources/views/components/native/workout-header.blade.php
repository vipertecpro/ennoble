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
        <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">{{ $eyebrow }}</native:text>
        <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">{{ $title }}</native:text>
        @if ($subtitle)
            <native:text class="text-[17] leading-relaxed text-theme-secondary-text">{{ $subtitle }}</native:text>
        @endif
    </native:column>
</native:column>
