@props([
    'label',
    'emphasis' => false,
    'motionDuration' => 0,
])

<native:column
    class="rounded-full px-3 py-1 {{ $emphasis ? 'bg-theme-selected' : 'bg-theme-secondary-surface' }}"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    <native:text class="text-[11] font-semibold {{ $emphasis ? 'text-theme-primary-text' : 'text-theme-muted-text' }}">
        {{ $label }}
    </native:text>
</native:column>
