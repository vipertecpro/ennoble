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
        $slotClass = 'bg-linear-to-r from-lime-400 to-cyan-400 rounded-2xl px-5 py-1 shadow-lg';
        $ink = 'text-black';
        $underline = 'bg-transparent';
    } elseif ($wrong) {
        $slotClass = 'px-3 py-1';
        $ink = 'text-theme-danger';
        $underline = 'bg-linear-to-r from-red-600 to-red-500';
    } else {
        $slotClass = 'px-3 py-1';
        $ink = 'text-theme-primary-text';
        $underline = 'bg-linear-to-r from-lime-400 to-cyan-400';
    }

    $scale = ($correct && ! $reducedMotion) ? 1.08 : 1.0;
@endphp

<native:row class="w-full items-center justify-center gap-3">
    <native:text class="text-[34] font-bold tracking-tight text-theme-primary-text">{{ $expression }} =</native:text>

    {{-- The typed digits sit DIRECTLY in this column (items-center centres them),
         never inside a justify-center nested row: on iOS a row measures its
         children with an unbounded width proposal, so a centred inner row here
         collapsed to zero and the digits vanished while the w-full underline
         (a column child) survived. Keeping the value as a direct column child
         renders it reliably on both platforms. --}}
    <native:column
        native:key="qm-slot-{{ $serial }}-{{ $tone }}"
        class="items-center gap-1 min-w-[76] {{ $correct ? $slotClass : '' }}"
        :scale="$scale"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    >
        <native:text class="text-[34] font-bold tracking-tight text-center {{ $ink }}">
            {{ $display === '' ? ' ' : $display }}{{ $correct ? '  ✓' : ($wrong ? '  ✗' : '') }}
        </native:text>

        @unless ($correct)
            <native:column class="h-1 w-full rounded-full {{ $underline }}" />
        @endunless
    </native:column>
</native:row>
