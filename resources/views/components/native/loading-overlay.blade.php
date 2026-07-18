@props([
    'mode' => 'full-screen',
    'label' => 'Loading',
])

@if ($mode === 'button')
    <native:button :label="$label" :loading="true" :disabled="true" :a11y-label="$label" />
@elseif ($mode === 'inline')
    <native:row class="items-center justify-center gap-3 py-4">
        <native:activity-indicator size="sm" a11y-label="{{ $label }}" />
        <native:text class="text-[15] text-theme-secondary-text">{{ $label }}</native:text>
    </native:row>
@else
    <native:column class="h-full items-center justify-center gap-3 bg-theme-background">
        <native:activity-indicator size="lg" a11y-label="{{ $label }}" />
        <native:text class="text-[17] text-theme-secondary-text">{{ $label }}</native:text>
    </native:column>
@endif
