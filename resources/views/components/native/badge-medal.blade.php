@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

{{--
    A single tier medal. Tinted to its Bronze/Silver/Gold color when unlocked,
    greyed with a lock when still to earn. The tier hex is read from the loaded
    theme so it repaints with the rest of the UI.
--}}

@props([
    'color',             // tier color token, e.g. 'badge-gold'
    'tierLabel' => '',   // 'Bronze' | 'Silver' | 'Gold' — for accessibility
    'unlocked' => false,
    'size' => 'lg',      // 'lg' (wall) | 'sm' (compact row)
])

@php
    $tierColor = config('native-ui.theme.light.'.$color, '#C79A25');
    $tierColorDark = config('native-ui.theme.dark.'.$color, '#E7C24B');
    $mutedColor = config('native-ui.theme.light.muted-text', '#90909A');
    $mutedColorDark = config('native-ui.theme.dark.muted-text', '#6B6B74');
    $disc = $size === 'sm' ? 'w-11 h-11' : 'w-16 h-16';
    $iconSize = $size === 'sm' ? 20 : 26;
@endphp

<native:column
    class="{{ $disc }} items-center justify-center rounded-full border-2 {{ $unlocked ? 'border-theme-'.$color.' bg-theme-surface-elevated shadow-sm' : 'border-theme-border bg-theme-secondary-surface' }}"
>
    <x-native.icon
        :ios="$unlocked ? Ios::Medal : Ios::Lock"
        :android="$unlocked ? AndroidOutlined::MilitaryTech : AndroidOutlined::Lock"
        :size="$iconSize"
        :color="$unlocked ? $tierColor : $mutedColor"
        :dark-color="$unlocked ? $tierColorDark : $mutedColorDark"
        :a11y-label="$unlocked ? $tierLabel.' badge earned' : $tierLabel.' badge locked'"
    />
</native:column>
