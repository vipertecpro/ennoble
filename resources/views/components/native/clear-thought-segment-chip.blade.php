@props([
    'segmentId',
    'text',
    'variant' => 'pool',
    'used' => false,
    'position' => null,
    'motionDuration' => 0,
])

@if ($variant === 'arranged')
    <native:pressable
        native:key="clear-thought-arranged-{{ $segmentId }}"
        class="rounded-full border border-theme-border bg-theme-primary-surface px-3 py-2"
        :press-scale="0.95"
        :animate-duration="$motionDuration"
        a11y-label="Position {{ $position }}: {{ $text }}"
        a11y-hint="Returns this segment to the unplaced pool"
        @press="removeArranged('{{ $segmentId }}')"
    >
        <native:row class="items-center gap-2">
            <native:text class="text-[13] font-semibold text-theme-muted-text">{{ $position }}</native:text>
            <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $text }}</native:text>
        </native:row>
    </native:pressable>
@else
    <native:pressable
        native:key="clear-thought-segment-{{ $segmentId }}"
        class="rounded-full border {{ $used ? 'border-theme-divider bg-theme-background opacity-30' : 'border-theme-border bg-theme-surface-elevated' }} px-3 py-2"
        :press-scale="$used ? 1.0 : 0.95"
        :animate-duration="$motionDuration"
        a11y-label="{{ $used ? $text.', already placed' : $text }}"
        a11y-hint="{{ $used ? 'Already placed in the sentence' : 'Places this segment next in the sentence' }}"
        @press="tapSegment('{{ $segmentId }}')"
    >
        <native:text class="text-[17] font-semibold {{ $used ? 'text-theme-muted-text' : 'text-theme-primary-text' }}">
            {{ $text }}
        </native:text>
    </native:pressable>
@endif
