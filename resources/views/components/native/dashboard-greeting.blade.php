@props([
    'greeting',
    'displayName',
    'message',
    'motionDuration' => 0,
])

@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:row
    class="items-center justify-between gap-4"
    :translate-y="$motionDuration > 0 ? -2 : 0"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $greeting }}, {{ $displayName }}"
>
    <native:column class="flex-1 gap-1">
        <native:text class="text-[15] font-semibold text-theme-muted-text">{{ $greeting }}</native:text>
        <native:text class="text-[22] font-semibold tracking-tight leading-tight text-theme-primary-text">
            {{ $displayName }}
        </native:text>
        <native:text class="text-[15] leading-relaxed text-theme-secondary-text">
            {{ $message }}
        </native:text>
    </native:column>
    <native:column class="w-14 h-14 items-center justify-center rounded-full bg-theme-primary-surface">
        <x-native.icon
            :ios="Ios::BrainHeadProfile"
            :android="AndroidOutlined::Psychology"
            :size="28"
            a11y-label="Ennoble"
        />
    </native:column>
</native:row>
