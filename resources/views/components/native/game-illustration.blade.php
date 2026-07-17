@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'slug',
    'hero' => false,
    'motionDuration' => 0,
])

@php
    [$ios, $android, $label] = match ($slug) {
        'signal-shift' => [Ios::Point3ConnectedTrianglepathDotted, AndroidOutlined::Route, 'Abstract shifting signal illustration'],
        'clear-thought' => [Ios::Textformat, AndroidOutlined::TextFields, 'Abstract language clarity illustration'],
        'memory-path' => [Ios::BrainHeadProfile, AndroidOutlined::Memory, 'Abstract memory path illustration'],
        'pattern-pulse' => [Ios::CircleGrid3x3, AndroidOutlined::Pattern, 'Abstract pattern pulse illustration'],
        'word-forge' => [Ios::TextformatAbc, AndroidOutlined::Abc, 'Abstract word forge illustration'],
        'quick-read' => [Ios::BookPages, AndroidOutlined::MenuBook, 'Abstract quick reading illustration'],
        'number-sense' => [Ios::NumberSquare, AndroidOutlined::Numbers, 'Abstract number sense illustration'],
        'reaction-pulse' => [Ios::BoltCircle, AndroidOutlined::Bolt, 'Abstract reaction pulse illustration'],
        default => [Ios::Gamecontroller, AndroidOutlined::SportsEsports, 'Abstract game illustration'],
    };
@endphp

<native:column
    class="{{ $hero ? 'w-full h-40' : 'w-16 h-16' }} items-center justify-center rounded-3xl bg-theme-surface-variant"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    <native:column class="{{ $hero ? 'w-24 h-24' : 'w-12 h-12' }} items-center justify-center rounded-full border border-theme-outline bg-theme-surface">
        <x-native.icon
            :ios="$ios"
            :android="$android"
            :size="$hero ? 48 : 28"
            :a11y-label="$label"
        />
    </native:column>
</native:column>
