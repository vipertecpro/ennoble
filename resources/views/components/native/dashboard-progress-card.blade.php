@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'skillHighlights' => [],
    'weeklyCompleted' => 0,
    'weeklyCompletionPercentage' => 0,
    'personalBestScore' => null,
    'personalBestGame' => null,
    'hasWorkoutHistory' => false,
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-5 rounded-3xl bg-theme-surface p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    @if (count($skillHighlights) === 0)
        <native:row class="w-full items-center gap-4">
            <native:column class="items-center justify-center rounded-2xl bg-theme-surface-variant p-3">
                <x-native.icon
                    :ios="Ios::ChartBar"
                    :android="AndroidOutlined::TrendingUp"
                    :size="28"
                    a11y-label="No skill progress yet"
                />
            </native:column>
            <native:column class="flex-1 gap-1">
                <native:text class="text-lg font-semibold text-theme-on-surface">No skill progress yet</native:text>
                <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
                    Your first completed activity will create an evidence-backed skill snapshot.
                </native:text>
            </native:column>
        </native:row>
    @else
        <native:column class="w-full gap-4">
            @foreach ($skillHighlights as $skill)
                <native:column class="w-full gap-2">
                    <native:row class="w-full items-center justify-between">
                        <native:text class="text-sm font-semibold text-theme-on-surface">{{ $skill['label'] }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">{{ $skill['score'] }} / 1000</native:text>
                    </native:row>
                    <native:progress-bar
                        :value="$skill['progress']"
                        a11y-label="{{ $skill['label'] }} skill score {{ $skill['score'] }} out of 1000"
                    />
                </native:column>
            @endforeach
        </native:column>
    @endif

    <native:divider />

    <native:row class="w-full items-start gap-4">
        <native:column class="flex-1 gap-1">
            <native:text class="text-xs font-semibold text-theme-on-surface-variant">THIS WEEK</native:text>
            <native:text class="text-xl font-bold text-theme-on-surface">{{ $weeklyCompleted }} of 7 days</native:text>
            <native:text class="text-sm text-theme-on-surface-variant">{{ $weeklyCompletionPercentage }}% complete</native:text>
        </native:column>
        <native:column class="flex-1 items-end gap-1">
            <native:text class="text-xs font-semibold text-theme-on-surface-variant">PERSONAL BEST</native:text>
            @if ($personalBestScore !== null)
                <native:text class="text-xl font-bold text-theme-on-surface">{{ $personalBestScore }}</native:text>
                <native:text class="text-sm text-theme-on-surface-variant">{{ $personalBestGame }}</native:text>
            @else
                <native:text class="text-base font-semibold text-theme-on-surface">Ready to set</native:text>
                <native:text class="text-sm text-theme-on-surface-variant">No score yet</native:text>
            @endif
        </native:column>
    </native:row>

    @unless ($hasWorkoutHistory)
        <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
            No workout history yet. Today can be your first useful baseline.
        </native:text>
    @endunless
</native:column>
