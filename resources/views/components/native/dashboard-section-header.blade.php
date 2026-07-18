@props([
    'title',
    'eyebrow' => null,
])

<native:column class="gap-1">
    @if ($eyebrow)
        <native:text class="text-xs font-semibold text-theme-accent">
            {{ $eyebrow }}
        </native:text>
    @endif
    <native:text class="text-xl font-semibold leading-tight text-theme-primary-text">
        {{ $title }}
    </native:text>
</native:column>
