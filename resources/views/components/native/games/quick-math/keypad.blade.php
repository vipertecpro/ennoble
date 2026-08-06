@use('App\Icons\Ios')
@use('App\Icons\Android')

@props([
    'disabled' => false,
    'reducedMotion' => false,
])

{{-- Game-premium numeric keypad — each key is a subtle gradient tile with a
     faint lime glow border, and the submit key is a bright lime → cyan glossy
     gradient. Presses are ignored server-side unless a round is live, so the
     disabled state here is purely visual (dimmed during the reward beat). --}}
@php
    $rows = [[7, 8, 9], [4, 5, 6], [1, 2, 3]];
    $keyBase = 'flex-1 h-16 items-center justify-center rounded-2xl border shadow-sm';
    $keyFill = 'bg-theme-surface border-rose-500/15';
    $submitFill = 'bg-linear-to-r from-rose-500 to-orange-400 border-rose-400/50 shadow-lg';
    $digitInk = 'text-[30] font-bold text-theme-primary-text';
    $pressScale = ($disabled || $reducedMotion) ? 1.0 : 0.9;
    $dim = $disabled ? 'opacity-40' : '';
@endphp

<native:column class="w-full px-6 pb-6 gap-2 {{ $dim }}">
    @foreach ($rows as $row)
        <native:row class="w-full gap-2">
            @foreach ($row as $digit)
                <native:pressable
                    class="{{ $keyBase }} {{ $keyFill }}"
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

    <native:row class="w-full gap-2">
        <native:pressable
            class="{{ $keyBase }} {{ $keyFill }}"
            :press-scale="$pressScale"
            :press-opacity="0.5"
            a11y-label="Delete last digit"
            @press="deleteKey"
        >
            <x-native.ui.icon :ios="Ios::DeleteLeft" :android="Android::Backspace" :size="26" />
        </native:pressable>

        <native:pressable
            class="{{ $keyBase }} {{ $keyFill }}"
            :press-scale="$pressScale"
            :press-opacity="0.5"
            a11y-label="0"
            @press="pressKey('0')"
        >
            <native:text class="{{ $digitInk }}">0</native:text>
        </native:pressable>

        <native:pressable
            class="{{ $keyBase }} {{ $submitFill }}"
            :press-scale="$pressScale"
            :press-opacity="0.5"
            a11y-label="Submit answer"
            @press="submitAnswer"
        >
            <x-native.ui.icon :ios="Ios::Checkmark" :android="Android::Check" :size="30" color="#000000" dark-color="#000000" />
        </native:pressable>
    </native:row>
</native:column>
