@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'duration',
    'gamesCompleted',
    'skills',
    'scoreSummary',
    'accuracySummary',
    'progressMessage',
    'motionDuration' => 0,
])

<native:column
    class="w-80 items-center rounded-3xl bg-theme-primary-surface py-5"
    :animate-duration="$motionDuration"
    :a11y-label="'Workout complete. '.$gamesCompleted.' games completed in '.$duration.'.'"
>
<native:column class="w-72 gap-5">
    <native:column class="items-center gap-3">
        <native:column class="items-center justify-center rounded-3xl bg-theme-primary-surface p-5">
            <x-native.icon
                :ios="Ios::CheckmarkSeal"
                :android="AndroidOutlined::CheckCircle"
                :size="40"
                a11y-label="Workout complete"
            />
        </native:column>
        <native:text class="text-3xl font-bold text-center text-theme-primary-text">Workout complete</native:text>
        <native:text class="text-base text-center leading-relaxed text-theme-secondary-text">
            You completed today’s practice with a calm, focused rhythm.
        </native:text>
    </native:column>

    <native:row class="gap-3">
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary-surface p-4">
            <native:text class="text-xs font-semibold text-theme-muted-text">TOTAL TIME</native:text>
            <native:text class="text-xl font-bold text-theme-primary-text">{{ $duration }}</native:text>
        </native:column>
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary-surface p-4">
            <native:text class="text-xs font-semibold text-theme-muted-text">GAMES COMPLETE</native:text>
            <native:text class="text-xl font-bold text-theme-primary-text">{{ $gamesCompleted }}</native:text>
        </native:column>
    </native:row>

    <native:column class="gap-2">
        <native:text class="text-xs font-semibold text-theme-accent">SKILLS INCLUDED</native:text>
        <native:text class="text-sm leading-relaxed text-theme-primary-text">{{ implode(' · ', $skills) }}</native:text>
        <native:text class="text-sm leading-relaxed text-theme-secondary-text">
            {{ $progressMessage }}
        </native:text>
    </native:column>

    <native:row class="gap-3">
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary-surface p-4">
            <native:text class="text-xs font-semibold text-theme-muted-text">SCORE</native:text>
            <native:text class="text-base font-bold text-theme-primary-text">{{ $scoreSummary }}</native:text>
        </native:column>
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary-surface p-4">
            <native:text class="text-xs font-semibold text-theme-muted-text">ACCURACY</native:text>
            <native:text class="text-base font-bold text-theme-primary-text">{{ $accuracySummary }}</native:text>
        </native:column>
    </native:row>
</native:column>
</native:column>
