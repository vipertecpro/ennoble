@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'duration',
    'gamesCompleted',
    'skills',
    'scoreSummary',
    'accuracySummary',
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-5 rounded-3xl bg-theme-primary p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="Workout complete. {{ $gamesCompleted }} games completed in {{ $duration }}."
>
    <native:column class="w-full items-center gap-3">
        <native:column class="items-center justify-center rounded-full bg-theme-secondary p-5">
            <x-native.icon
                :ios="Ios::CheckmarkSeal"
                :android="AndroidOutlined::CheckCircle"
                :size="40"
                a11y-label="Workout complete"
            />
        </native:column>
        <native:text class="text-3xl font-bold text-center text-theme-on-primary">Workout complete</native:text>
        <native:text class="text-base text-center leading-relaxed text-theme-on-primary">
            You moved through the complete session framework with a calm, focused rhythm.
        </native:text>
    </native:column>

    <native:row class="w-full gap-3">
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary p-4">
            <native:text class="text-xs font-semibold text-theme-on-secondary">TOTAL TIME</native:text>
            <native:text class="text-xl font-bold text-theme-on-secondary">{{ $duration }}</native:text>
        </native:column>
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary p-4">
            <native:text class="text-xs font-semibold text-theme-on-secondary">GAMES COMPLETE</native:text>
            <native:text class="text-xl font-bold text-theme-on-secondary">{{ $gamesCompleted }}</native:text>
        </native:column>
    </native:row>

    <native:column class="w-full gap-2">
        <native:text class="text-xs font-semibold text-theme-on-primary">SKILLS INCLUDED</native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-primary">{{ implode(' · ', $skills) }}</native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-primary">
            Skill progress was not recorded because gameplay is intentionally deferred.
        </native:text>
    </native:column>

    <native:row class="w-full gap-3">
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary p-4">
            <native:text class="text-xs font-semibold text-theme-on-secondary">SCORE</native:text>
            <native:text class="text-base font-bold text-theme-on-secondary">{{ $scoreSummary }}</native:text>
        </native:column>
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary p-4">
            <native:text class="text-xs font-semibold text-theme-on-secondary">ACCURACY</native:text>
            <native:text class="text-base font-bold text-theme-on-secondary">{{ $accuracySummary }}</native:text>
        </native:column>
    </native:row>
</native:column>
