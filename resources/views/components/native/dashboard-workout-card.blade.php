@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\Enums\WorkoutStatus')

@props([
    'title',
    'duration',
    'gameCount' => 0,
    'difficulty',
    'action',
    'status',
    'completionPercentage' => 0,
    'motionDuration' => 0,
])

{{-- Cortex "Today's session" hero — the one card that earns the screen's lime
     moment. Uppercase accent eyebrow, display title, a single meta line
     (games · duration · pace), a lime-tinted icon badge, and the full-width
     solid-lime CTA embedded in the card. --}}
<native:column
    class="w-full rounded-2xl bg-theme-surface-elevated shadow-sm p-5 gap-5"
    :animate-duration="$motionDuration"
    a11y-label="{{ $title }}, {{ $completionPercentage }} percent complete"
>
    <native:row class="w-full items-start justify-between gap-4">
        <native:column class="flex-1 gap-1">
            <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">TODAY’S SESSION</native:text>
            <native:text class="text-[22] font-semibold tracking-tight leading-tight text-theme-primary-text">{{ $title }}</native:text>
            <native:text class="text-[13] text-theme-secondary-text">{{ $gameCount }} {{ $gameCount === 1 ? 'game' : 'games' }} · {{ $duration }} · {{ $difficulty }}</native:text>
        </native:column>
        <native:column class="w-11 h-11 items-center justify-center rounded-2xl bg-theme-primary-surface">
            <x-native.icon
                :ios="$status === WorkoutStatus::Completed->value ? Ios::CheckmarkSeal : Ios::BrainHeadProfile"
                :android="$status === WorkoutStatus::Completed->value ? AndroidOutlined::CheckCircle : AndroidOutlined::Psychology"
                :size="22"
                :a11y-label="$status === WorkoutStatus::Completed->value ? 'Workout completed' : 'Brain training'"
            />
        </native:column>
    </native:row>

    @if ($completionPercentage > 0)
        <native:column class="w-full gap-2">
            <native:row class="w-full items-center justify-between">
                <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-muted-text">Progress</native:text>
                <native:text class="text-[13] font-semibold text-theme-accent">{{ $completionPercentage }}%</native:text>
            </native:row>
            <native:progress-bar
                :value="$completionPercentage / 100"
                a11y-label="Today’s workout is {{ $completionPercentage }} percent complete"
            />
        </native:column>
    @endif

    @if ($status === WorkoutStatus::Completed->value)
        <native:text class="text-[15] font-semibold text-theme-accent">Complete for today</native:text>
    @else
        <native:button
            class="w-full"
            :label="$action"
            size="md"
            variant="primary"
            a11y-hint="Opens the workout flow preview"
            @press="openWorkout"
        />
    @endif
</native:column>
