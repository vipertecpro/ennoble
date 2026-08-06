@use('App\NativeUI\Tokens\Gradients')
@props([
    'label',
    'emphasis' => false,
    'motionDuration' => 0,
])

<native:column
    class="rounded-full px-3 py-1 {{ $emphasis ? 'bg-linear-to-r from-rose-500/30 to-orange-400/20 border border-rose-500/40 shadow-lg' : 'bg-theme-secondary-surface border '.Gradients::hairline() }}"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    <native:text class="text-[11] font-semibold {{ $emphasis ? 'text-rose-500' : 'text-theme-muted-text' }}">
        {{ $label }}
    </native:text>
</native:column>
