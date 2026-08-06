@use('App\NativeUI\Tokens\Gradients')
@props([
    'tiles' => 9,
    'sequence' => [],
    'playbackStep' => -1,
    'phase' => 'watch',
    'feedbackTone' => 'idle',
    'lastTile' => -1,
    'tapSerial' => 0,
    'feedbackSerial' => 0,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

{{-- The memory grid. During "watch" the current step lights up with a glowing
     lime→teal gradient; during "recall" a tapped tile flashes lime→teal
     (correct) or red (wrong); a completed sequence lights the whole board. Idle
     tiles use a theme-surface gradient so they adapt to light and dark mode. --}}
@php
    $current = ($playbackStep >= 0 && isset($sequence[$playbackStep])) ? (int) $sequence[$playbackStep] : -1;
    $rows = array_chunk(range(0, max(0, $tiles - 1)), 3);
    $tappable = ($phase === 'recall' && $feedbackTone === 'idle');
@endphp

<native:column class="w-full px-6 gap-3">
    @foreach ($rows as $row)
        <native:row class="w-full gap-3">
            @foreach ($row as $tile)
                @php
                    $lit = ($phase === 'watch' && $current === $tile);
                    $isLast = ($lastTile === $tile);
                    $wrong = ($isLast && $feedbackTone === 'wrong');
                    $tapGlow = ($isLast && $feedbackTone === 'idle' && $phase === 'recall');
                    $win = ($feedbackTone === 'correct');

                    if ($lit) {
                        $bg = 'bg-linear-to-br from-rose-400 to-teal-400 border border-rose-300/70 shadow-lg';
                        $key = 'tile-'.$tile.'-lit-'.$playbackStep;
                        $scale = 1.12;
                    } elseif ($wrong) {
                        $bg = 'bg-linear-to-br from-red-500 to-red-600 border border-red-400/70 shadow-lg';
                        $key = 'tile-'.$tile.'-tap-'.$tapSerial;
                        $scale = 1.05;
                    } elseif ($tapGlow) {
                        $bg = 'bg-linear-to-br from-rose-500 to-teal-500 border border-rose-400/70 shadow-lg';
                        $key = 'tile-'.$tile.'-tap-'.$tapSerial;
                        $scale = 1.08;
                    } elseif ($win) {
                        $bg = 'bg-linear-to-br from-rose-400 to-teal-400 border border-rose-300/70 shadow-lg';
                        $key = 'tile-'.$tile.'-win-'.$feedbackSerial;
                        $scale = 1.04;
                    } else {
                        $bg = 'bg-theme-surface border '.Gradients::hairline().' shadow-sm';
                        $key = 'tile-'.$tile;
                        $scale = 1.0;
                    }
                @endphp

                <native:pressable
                    native:key="{{ $key }}"
                    class="flex-1 h-24 rounded-2xl {{ $bg }}"
                    :scale="$reducedMotion ? 1 : $scale"
                    :animate-duration="$motionDuration"
                    animate-easing="ease-out"
                    :press-scale="$tappable && ! $reducedMotion ? 0.96 : 1"
                    a11y-label="Tile {{ $tile + 1 }}"
                    @press="tapTile('{{ $tile }}')"
                >
                    <native:column class="w-full h-full" />
                </native:pressable>
            @endforeach
        </native:row>
    @endforeach
</native:column>
