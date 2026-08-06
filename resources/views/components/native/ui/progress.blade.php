@use('Native\Mobile\UI\Theme')

{{--
    Semantic progress bar (reference "ability unlocked" style): an optional
    label + value row over a native progress-bar tinted to a theme token.
    `token` names any color token (accent, accent-cyan, accent-violet, …);
    neon accents are identical across themes, so a single resolved hex is safe.
--}}
@props([
    'value' => 0,
    'token' => 'accent',
    'label' => null,
    'valueLabel' => null,
])

@php
    $tokens = Theme::all();
    $fill = data_get($tokens, "light.{$token}")
        ?? data_get($tokens, 'light.accent', '#C5DB55');
    $fraction = max(0.0, min(1.0, (float) $value));
@endphp

<native:column class="w-full gap-1.5">
    @if ($label !== null || $valueLabel !== null)
        <native:row class="w-full items-center">
            <native:text class="flex-1 text-[12.5] text-theme-secondary-text">{{ $label }}</native:text>
            @if ($valueLabel !== null)
                <native:text font="numeric" class="text-[12.5] text-theme-primary-text">{{ $valueLabel }}</native:text>
            @endif
        </native:row>
    @endif

    <native:progress-bar
        :value="$fraction"
        :color="$fill"
        class="w-full h-2.5 rounded-full"
        a11y-label="{{ $label ?? 'Progress' }}"
    />
</native:column>
