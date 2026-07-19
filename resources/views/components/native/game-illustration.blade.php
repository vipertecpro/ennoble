@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'slug',
    'hero' => false,
    'motionDuration' => 0,
])

@php
    [$ios, $android, $label] = match ($slug) {
        'word-match' => [Ios::TextformatAbc, AndroidOutlined::Abc, 'Abstract word matching illustration'],
        'quick-math' => [Ios::NumberSquare, AndroidOutlined::Numbers, 'Abstract quick math illustration'],
        default => [Ios::Gamecontroller, AndroidOutlined::SportsEsports, 'Abstract game illustration'],
    };
@endphp

<native:column
    class="{{ $hero ? 'h-40' : 'w-16 h-16' }} items-center justify-center rounded-2xl bg-theme-primary-surface"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    <native:column class="{{ $hero ? 'w-24 h-24' : 'w-12 h-12' }} items-center justify-center rounded-full bg-theme-surface-elevated shadow-sm">
        <x-native.icon
            :ios="$ios"
            :android="$android"
            :size="$hero ? 48 : 28"
            :a11y-label="$label"
        />
    </native:column>
</native:column>
