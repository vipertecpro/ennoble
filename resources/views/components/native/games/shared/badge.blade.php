@use('App\NativeUI\Tokens\Gradients')
@props([
    'label',
    'emphasis' => false,
    'motionDuration' => 0,
])

<native:column
    class="rounded-full px-3 py-1 {{ $emphasis ? 'bg-linear-to-r from-lime-400/30 to-cyan-500/20 border border-lime-400/40 shadow-lg' : 'bg-theme-secondary-surface border '.Gradients::hairline() }}"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    <native:text class="text-[11] font-semibold {{ $emphasis ? 'text-lime-400' : 'text-theme-muted-text' }}">
        {{ $label }}
    </native:text>
</native:column>
