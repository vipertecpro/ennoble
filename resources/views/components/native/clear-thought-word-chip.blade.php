@props([
    'wordId',
    'text',
    'selected' => false,
    'motionDuration' => 0,
])

<native:pressable
    native:key="clear-thought-word-{{ $wordId }}"
    class="rounded-full border {{ $selected ? 'border-theme-danger bg-theme-secondary-surface' : 'border-theme-border bg-theme-surface-elevated' }} px-3 py-2"
    :press-scale="0.95"
    :animate-duration="$motionDuration"
    a11y-label="{{ $text }}{{ $selected ? ', marked for removal' : '' }}"
    a11y-hint="{{ $selected ? 'Keeps this word in the sentence' : 'Marks this word as unnecessary' }}"
    @press="toggleWord('{{ $wordId }}')"
>
    <native:text class="text-[17] font-semibold {{ $selected ? 'text-theme-danger' : 'text-theme-primary-text' }}">
        {{ $text }}
    </native:text>
</native:pressable>
