{{--
    Glossy gradient primary button — the "game-premium" CTA.

    A bright lime -> cyan gradient pressable with a bold, dark, centred label,
    a full-round shape, depth via shadow, and press-scale feedback. The @press
    handler is passed through by method name.

    NOTE: the label sits directly in the <native:column> (not in a nested
    justify-center row) to dodge the iOS flex gotcha where a nested centred row
    collapses to zero width and hides its content.
--}}

@props([
    'label',
    'press' => null,
    'pressScale' => 0.96,
    'class' => '',
])

<native:pressable
    class="w-full rounded-full bg-linear-to-r from-rose-500 to-orange-400 shadow-lg py-4 px-6 {{ $class }}"
    :press-scale="$pressScale"
    a11y-label="{{ $label }}"
    @if ($press) @press="{{ $press }}" @endif
>
    <native:column class="w-full items-center">
        <native:text class="text-[17] font-bold tracking-tight text-black">{{ $label }}</native:text>
    </native:column>
</native:pressable>
