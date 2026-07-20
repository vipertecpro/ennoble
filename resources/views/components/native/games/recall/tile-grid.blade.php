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

{{-- The memory grid. During "watch" the current step lights up in the accent;
     during "recall" a tapped tile flashes accent (correct) or danger (wrong); a
     completed sequence lights the whole board. Colours come from theme tokens,
     so the game's scoped violet accent flows through automatically. --}}
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
                        $bg = 'bg-theme-accent';
                        $key = 'tile-'.$tile.'-lit-'.$playbackStep;
                        $scale = 1.12;
                    } elseif ($wrong) {
                        $bg = 'bg-theme-danger';
                        $key = 'tile-'.$tile.'-tap-'.$tapSerial;
                        $scale = 1.05;
                    } elseif ($tapGlow) {
                        $bg = 'bg-theme-accent';
                        $key = 'tile-'.$tile.'-tap-'.$tapSerial;
                        $scale = 1.08;
                    } elseif ($win) {
                        $bg = 'bg-theme-accent';
                        $key = 'tile-'.$tile.'-win-'.$feedbackSerial;
                        $scale = 1.04;
                    } else {
                        $bg = 'bg-theme-surface-elevated';
                        $key = 'tile-'.$tile;
                        $scale = 1.0;
                    }
                @endphp

                <native:pressable
                    native:key="{{ $key }}"
                    class="flex-1 h-24 rounded-2xl border border-theme-border shadow-sm {{ $bg }}"
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
