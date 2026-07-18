@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'message',
    'streak',
    'achievement' => null,
    'motionDuration' => 0,
])

<native:column
    class="w-80 gap-4 rounded-2xl bg-theme-primary-surface shadow-sm p-5"
    :animate-duration="$motionDuration"
    a11y-label="Workout completed. {{ $message }} {{ $streak }}{{ $achievement ? '. Achievement unlocked: '.$achievement : '' }}"
>
    <native:row class="items-center gap-4">
        <native:column class="h-14 w-14 items-center justify-center rounded-full bg-theme-surface-elevated">
            <x-native.icon :ios="Ios::CheckmarkSeal" :android="AndroidOutlined::CheckCircle" :size="28" />
        </native:column>
        <native:column class="flex-1 gap-1">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-accent">WORKOUT COMPLETED</native:text>
            <native:text class="text-[17] font-semibold leading-tight text-theme-primary-text">{{ $message }}</native:text>
        </native:column>
    </native:row>

    <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ $streak }}</native:text>

    @if ($achievement)
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Trophy" :android="AndroidOutlined::EmojiEvents" :size="18" />
            <native:text class="text-[15] font-semibold text-theme-primary-text">Achievement unlocked · {{ $achievement }}</native:text>
        </native:row>
    @endif
</native:column>
