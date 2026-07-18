@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'count',
    'announcement',
    'motionDuration' => 0,
])

<native:column
    class="items-center gap-4 rounded-3xl border border-theme-border bg-theme-primary-surface p-8"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    :a11y-label="$announcement"
>
    <x-native.icon
        :ios="Ios::Timer"
        :android="AndroidOutlined::Timer"
        :size="40"
        a11y-label="Preparation countdown"
    />
    <native:text
        class="text-5xl font-bold text-theme-primary-text"
        :a11y-label="$announcement"
    >
        {{ $count > 0 ? $count : 'GO' }}
    </native:text>
    <native:text class="text-base font-semibold text-theme-secondary-text">
        {{ $count > 0 ? 'Breathe and get ready' : 'Begin when ready' }}
    </native:text>
</native:column>
