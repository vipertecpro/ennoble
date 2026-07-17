@props([
    'title',
    'eyebrow' => null,
])

<native:column class="w-full gap-1">
    @if ($eyebrow)
        <native:text class="text-xs font-semibold text-theme-primary">
            {{ $eyebrow }}
        </native:text>
    @endif
    <native:text class="text-xl font-semibold leading-tight text-theme-on-background">
        {{ $title }}
    </native:text>
</native:column>
