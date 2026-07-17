@props([
    'label',
])

<native:column
    class="w-full items-center gap-3 rounded-3xl bg-theme-surface p-5"
    :min-height="112"
>
    <native:activity-indicator size="sm" :a11y-label="$label" />
    <native:text class="text-sm text-theme-on-surface-variant">{{ $label }}</native:text>
</native:column>
