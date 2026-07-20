@use('App\Icons\Ios')
@use('App\Icons\Android')

@props([
    'disabled' => false,
    'reducedMotion' => false,
])

{{-- Transparent numeric keypad — just the glyphs in a 3-column grid, no key
     backgrounds. Presses are ignored server-side unless a round is live, so the
     disabled state here is purely visual (dimmed during the reward beat). --}}
@php
    $rows = [[7, 8, 9], [4, 5, 6], [1, 2, 3]];
    $keyBase = 'flex-1 h-16 items-center justify-center';
    $digitInk = 'text-[30] font-bold text-theme-primary-text';
    $pressScale = ($disabled || $reducedMotion) ? 1.0 : 0.9;
    $dim = $disabled ? 'opacity-40' : '';
@endphp

<native:column class="w-full px-6 pb-6 gap-1 {{ $dim }}">
    @foreach ($rows as $row)
        <native:row class="w-full gap-1">
            @foreach ($row as $digit)
                <native:pressable
                    class="{{ $keyBase }}"
                    :press-scale="$pressScale"
                    :press-opacity="0.5"
                    a11y-label="{{ $digit }}"
                    @press="pressKey('{{ $digit }}')"
                >
                    <native:text class="{{ $digitInk }}">{{ $digit }}</native:text>
                </native:pressable>
            @endforeach
        </native:row>
    @endforeach

    <native:row class="w-full gap-1">
        <native:pressable
            class="{{ $keyBase }}"
            :press-scale="$pressScale"
            :press-opacity="0.5"
            a11y-label="Delete last digit"
            @press="deleteKey"
        >
            <x-native.ui.icon :ios="Ios::DeleteLeft" :android="Android::Backspace" :size="26" />
        </native:pressable>

        <native:pressable
            class="{{ $keyBase }}"
            :press-scale="$pressScale"
            :press-opacity="0.5"
            a11y-label="0"
            @press="pressKey('0')"
        >
            <native:text class="{{ $digitInk }}">0</native:text>
        </native:pressable>

        <native:pressable
            class="{{ $keyBase }}"
            :press-scale="$pressScale"
            :press-opacity="0.5"
            a11y-label="Submit answer"
            @press="submitAnswer"
        >
            <x-native.ui.icon :ios="Ios::Checkmark" :android="Android::Check" :size="30" color="#C5DB55" dark-color="#C5DB55" />
        </native:pressable>
    </native:row>
</native:column>
