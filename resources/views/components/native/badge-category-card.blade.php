@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

{{--
    One activity category on the Achievements screen: its current measured value,
    earned/total across the 35 badges, a Bronze/Silver/Gold breakdown, and the
    next badge to chase. Tapping opens the category's badge grid.
--}}

@props([
    'category',
    'pressScale' => 1.0,
    'pressOpacity' => 1.0,
    'motionDuration' => 0,
])

@php
    [$ios, $android] = match ($category['key']) {
        'streak' => [Ios::Flame, AndroidOutlined::LocalFireDepartment],
        'accuracy' => [Ios::Target, AndroidOutlined::GpsFixed],
        'speed' => [Ios::Bolt, AndroidOutlined::Bolt],
        'dedication' => [Ios::Flag, AndroidOutlined::Flag],
        'mastery' => [Ios::Crown, AndroidOutlined::WorkspacePremium],
        default => [Ios::Rosette, AndroidOutlined::MilitaryTech],
    };
@endphp

<native:pressable
    class="w-full rounded-2xl bg-theme-surface shadow-sm p-4"
    :press-scale="$pressScale"
    :press-opacity="$pressOpacity"
    :animate-duration="$motionDuration"
    a11y-label="{{ $category['label'] }} badges"
    a11y-hint="Opens the {{ $category['label'] }} badge grid"
    @press="openCategory('{{ $category['key'] }}')"
>
    <native:column class="w-full gap-3">
        <native:row class="items-center gap-3">
            <native:column class="items-center justify-center rounded-xl bg-theme-secondary-surface p-3">
                <x-native.icon :ios="$ios" :android="$android" :size="24" />
            </native:column>
            <native:column class="flex-1 gap-1">
                <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $category['label'] }}</native:text>
                <native:text class="text-[12] leading-relaxed text-theme-muted-text">Now: {{ $category['currentLabel'] }}</native:text>
            </native:column>
            <native:column class="items-end gap-1">
                <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $category['earned'] }}/{{ $category['total'] }}</native:text>
                <x-native.icon
                    :ios="Ios::ChevronRight"
                    :android="AndroidOutlined::ChevronRight"
                    :size="16"
                />
            </native:column>
        </native:row>

        <native:row class="items-center gap-4">
            @foreach ($category['tiers'] as $tier)
                <x-native.badge-tier-pill
                    :label="$tier['label']"
                    :earned="$tier['earned']"
                    :total="$tier['total']"
                    :color="$tier['color']"
                />
            @endforeach
        </native:row>

        @if ($category['nextLabel'] !== null)
            <native:text class="text-[12] leading-relaxed text-theme-secondary-text">
                Next {{ $category['nextTier'] }}: {{ $category['nextLabel'] }}
            </native:text>
        @else
            <native:text class="text-[12] font-semibold text-theme-accent">All badges earned</native:text>
        @endif
    </native:column>
</native:pressable>
