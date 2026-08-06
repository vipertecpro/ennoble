@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')
@use('Native\Mobile\Edge\TailwindParser')

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
    [$ios, $android, $accent, $accentTo] = match ($category['key']) {
        'streak' => [Ios::Flame, AndroidOutlined::LocalFireDepartment, 'orange-500', 'red-600'],
        'accuracy' => [Ios::Target, AndroidOutlined::GpsFixed, 'rose-500', 'cyan-500'],
        'speed' => [Ios::Bolt, AndroidOutlined::Bolt, 'amber-400', 'orange-500'],
        'dedication' => [Ios::Flag, AndroidOutlined::Flag, 'cyan-500', 'rose-500'],
        'mastery' => [Ios::Crown, AndroidOutlined::WorkspacePremium, 'amber-400', 'yellow-500'],
        default => [Ios::Rosette, AndroidOutlined::MilitaryTech, 'rose-500', 'cyan-500'],
    };

    // Tint the glyph with its category hue (resolved to hex so it renders in
    // both modes) instead of flat ink — the icon then matches its chip.
    $iconColor = TailwindParser::resolveColorValue($accent);
@endphp

<native:pressable
    class="w-full rounded-3xl bg-theme-surface border {{ Gradients::hairline() }} shadow-sm p-4"
    :press-scale="$pressScale"
    :press-opacity="$pressOpacity"
    :animate-duration="$motionDuration"
    a11y-label="{{ $category['label'] }} badges"
    a11y-hint="Opens the {{ $category['label'] }} badge grid"
    @press="openCategory('{{ $category['key'] }}')"
>
    <native:column class="w-full gap-3">
        <native:row class="items-center gap-3">
            <native:column class="items-center justify-center rounded-2xl bg-linear-to-br from-{{ $accent }}/25 to-{{ $accentTo }}/10 border border-{{ $accent }}/35 p-3">
                <x-native.ui.icon :ios="$ios" :android="$android" :size="24" :color="$iconColor" :dark-color="$iconColor" />
            </native:column>
            <native:column class="flex-1 gap-1">
                <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $category['label'] }}</native:text>
                <native:text class="text-[12] leading-relaxed text-theme-muted-text">Now: {{ $category['currentLabel'] }}</native:text>
            </native:column>
            <native:column class="items-end gap-1">
                <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $category['earned'] }}/{{ $category['total'] }}</native:text>
                <x-native.ui.icon
                    :ios="Ios::ChevronRight"
                    :android="AndroidOutlined::ChevronRight"
                    :size="16"
                />
            </native:column>
        </native:row>

        <native:row class="items-center gap-4">
            @foreach ($category['tiers'] as $tier)
                <x-native.badges.tier-pill
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
