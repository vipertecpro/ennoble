{{--
    Compact stat chip for the dense "stat strip" (3-up / 4-up). Icon + value +
    label. Numeric values are thousands-formatted so a growing count stays
    readable and never overflows the chip.
--}}
@props([
    'value',
    'label',
    'token' => 'accent',
    'ios' => null,
    'android' => null,
])

@php
    $display = is_numeric($value) ? number_format((int) $value) : (string) $value;
@endphp

<native:column class="flex-1 items-center gap-1.5 rounded-2xl bg-theme-surface border border-theme-border py-3 px-1.5">
    @if ($ios !== null || $android !== null)
        <native:column class="w-7 h-7 items-center justify-center rounded-lg bg-theme-{{ $token }}/15">
            <native:icon :ios="$ios" :android="$android" :size="15" class="text-theme-{{ $token }}" />
        </native:column>
    @endif
    <native:text font="numeric" class="text-[17] leading-none text-theme-primary-text">{{ $display }}</native:text>
    <native:text class="text-[9] font-bold uppercase tracking-wider text-theme-muted-text">{{ $label }}</native:text>
</native:column>
