@props([
    'title',
    'eyebrow' => null,
])

<native:column class="gap-1">
    @if ($eyebrow)
        <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
            {{ $eyebrow }}
        </native:text>
    @endif
    <native:text class="text-[15] font-semibold leading-tight text-theme-primary-text">
        {{ $title }}
    </native:text>
</native:column>
