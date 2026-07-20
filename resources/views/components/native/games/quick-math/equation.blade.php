@use('App\Icons\Ios')
@use('App\Icons\Android')

@props([
    'expression',
    'answer',
    'typed' => '',
    'tone' => 'idle',
    'serial' => 0,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

{{-- The problem as a fill-in-the-blank equation: "7 × 3 = [ 21 ]". The slot
     fills with the accent on a correct answer (with a ✓), turns danger-red on a
     wrong answer / time-out (with a ✗), and otherwise shows the typed digits
     over an accent underline. --}}
@php
    $correct = $tone === 'correct';
    $wrong = in_array($tone, ['wrong', 'timeout'], true);

    $display = ($wrong && $typed === '') ? '—' : $typed;

    if ($correct) {
        $slotClass = 'bg-theme-accent rounded-2xl px-5 py-1';
        $ink = 'text-theme-on-accent';
        $underline = 'bg-transparent';
    } elseif ($wrong) {
        $slotClass = 'px-3 py-1';
        $ink = 'text-theme-danger';
        $underline = 'bg-theme-danger';
    } else {
        $slotClass = 'px-3 py-1';
        $ink = 'text-theme-primary-text';
        $underline = 'bg-theme-accent';
    }

    $scale = ($correct && ! $reducedMotion) ? 1.08 : 1.0;
@endphp

<native:row class="w-full items-center justify-center gap-3">
    <native:text class="text-[34] font-bold tracking-tight text-theme-primary-text">{{ $expression }} =</native:text>

    <native:column
        native:key="qm-slot-{{ $serial }}-{{ $tone }}"
        class="items-center gap-1 min-w-[76]"
        :scale="$scale"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    >
        <native:row class="items-center justify-center gap-2 {{ $slotClass }}">
            <native:text class="text-[34] font-bold tracking-tight {{ $ink }}">{{ $display === '' ? ' ' : $display }}</native:text>
            @if ($correct)
                <x-native.ui.icon :ios="Ios::Checkmark" :android="Android::Check" :size="22" color="#181C06" dark-color="#181C06" />
            @elseif ($wrong)
                <x-native.ui.icon :ios="Ios::Xmark" :android="Android::Close" :size="22" color="#C53637" dark-color="#F2716A" />
            @endif
        </native:row>

        @unless ($correct)
            <native:column class="h-1 w-full rounded-full {{ $underline }}" />
        @endunless
    </native:column>
</native:row>
