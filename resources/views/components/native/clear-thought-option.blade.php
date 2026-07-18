@props([
    'optionId',
    'text',
    'state' => 'idle',
    'motionDuration' => 0,
])

<native:pressable
    native:key="clear-thought-option-{{ $optionId }}"
    class="w-full rounded-2xl {{ $state === 'wrong' ? 'border border-theme-danger bg-theme-secondary-surface opacity-60' : 'bg-theme-surface shadow-sm' }} p-4"
    :press-scale="0.985"
    :animate-duration="$motionDuration"
    a11y-label="{{ $text }}"
    a11y-hint="{{ $state === 'wrong' ? 'Already tried. Choose a different version.' : 'Selects this version as the clearest sentence' }}"
    @press="chooseOption('{{ $optionId }}')"
>
    <native:text class="text-[17] leading-relaxed {{ $state === 'wrong' ? 'text-theme-muted-text' : 'text-theme-primary-text' }}">
        {{ $text }}
    </native:text>
</native:pressable>
