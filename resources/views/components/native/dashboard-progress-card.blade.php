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

<native:column class="w-80 items-center rounded-3xl border border-theme-border bg-theme-surface-elevated py-5" :animate-duration="$motionDuration">
<native:column class="w-72 gap-4">
    @if (count($skillHighlights) === 0)
        <native:row class="items-center gap-4">
            <native:column class="items-center justify-center rounded-2xl bg-theme-secondary-surface p-3">
                <x-native.icon
                    :ios="Ios::ChartBar"
                    :android="AndroidOutlined::TrendingUp"
                    :size="28"
                    a11y-label="No skill progress yet"
                />
            </native:column>
            <native:column class="flex-1 gap-1">
                <native:text class="text-lg font-semibold text-theme-primary-text">No skill progress yet</native:text>
                <native:text class="text-sm leading-relaxed text-theme-secondary-text">
                    Your first completed activity will create an evidence-backed skill snapshot.
                </native:text>
            </native:column>
        </native:row>
    @else
        <native:column class="gap-4">
            @foreach ($skillHighlights as $skill)
                <native:column class="gap-2">
                    <native:row class="items-center justify-between">
                        <native:text class="text-sm font-semibold text-theme-primary-text">{{ $skill['label'] }}</native:text>
                        <native:text class="text-sm text-theme-muted-text">{{ $skill['score'] }} / 1000</native:text>
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

    <native:row class="items-start gap-4">
        <native:column class="flex-1 gap-1">
            <native:text class="text-xs font-semibold text-theme-muted-text">THIS WEEK</native:text>
            <native:text class="text-xl font-bold text-theme-primary-text">{{ $weeklyCompleted }} of 7 days</native:text>
            <native:text class="text-sm text-theme-muted-text">{{ $weeklyCompletionPercentage }}% complete</native:text>
        </native:column>
        <native:column class="flex-1 items-end gap-1">
            <native:text class="text-xs font-semibold text-theme-muted-text">PERSONAL BEST</native:text>
            @if ($personalBestScore !== null)
                <native:text class="text-xl font-bold text-theme-primary-text">{{ $personalBestScore }}</native:text>
                <native:text class="text-sm text-theme-muted-text">{{ $personalBestGame }}</native:text>
            @else
                <native:text class="text-base font-semibold text-theme-primary-text">Ready to set</native:text>
                <native:text class="text-sm text-theme-muted-text">No score yet</native:text>
            @endif
        </native:column>
    </native:row>

    @unless ($hasWorkoutHistory)
        <native:text class="text-sm leading-relaxed text-theme-secondary-text">
            No workout history yet. Today can be your first useful baseline.
        </native:text>
    @endunless
</native:column>
</native:column>
