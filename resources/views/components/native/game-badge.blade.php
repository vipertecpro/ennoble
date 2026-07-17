@props([
    'label',
    'emphasis' => false,
    'motionDuration' => 0,
])

<native:column
    class="rounded-full px-3 py-1 {{ $emphasis ? 'bg-theme-primary' : 'bg-theme-surface-variant' }}"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    <native:text class="text-xs font-semibold {{ $emphasis ? 'text-theme-on-primary' : 'text-theme-on-surface-variant' }}">
        {{ $label }}
    </native:text>
</native:column>
