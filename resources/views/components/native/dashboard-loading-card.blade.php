@props([
    'label',
])

<native:column class="w-80 items-center rounded-2xl bg-theme-surface shadow-sm py-5">
<native:column class="w-72 gap-4">
    <native:column class="items-center gap-3" :min-height="112">
        <native:activity-indicator size="sm" :a11y-label="$label" />
        <native:text class="text-[15] text-theme-muted-text">{{ $label }}</native:text>
    </native:column>
</native:column>
</native:column>
