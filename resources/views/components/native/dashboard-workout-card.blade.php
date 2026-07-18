@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\Enums\WorkoutStatus')

@props([
    'title',
    'duration',
    'difficulty',
    'action',
    'status',
    'completionPercentage' => 0,
    'motionDuration' => 0,
])

<native:column
    class="w-80 items-center rounded-2xl bg-theme-surface-elevated shadow-sm py-5"
    :animate-duration="$motionDuration"
    a11y-label="{{ $title }}, {{ $completionPercentage }} percent complete"
>
<native:column class="w-72 gap-5">
    <native:row class="items-center justify-between gap-4">
        <native:column class="flex-1 gap-1">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-accent">TODAY’S PRACTICE</native:text>
            <native:text class="text-[22] font-semibold tracking-tight leading-tight text-theme-primary-text">{{ $title }}</native:text>
        </native:column>
        <native:column class="w-20 h-20 items-center justify-center rounded-2xl bg-theme-primary-surface">
            <x-native.icon
                :ios="$status === WorkoutStatus::Completed->value ? Ios::CheckmarkSeal : Ios::BrainHeadProfile"
                :android="$status === WorkoutStatus::Completed->value ? AndroidOutlined::CheckCircle : AndroidOutlined::Psychology"
                :size="36"
                :a11y-label="$status === WorkoutStatus::Completed->value ? 'Workout completed' : 'Brain training'"
            />
        </native:column>
    </native:row>

    <native:column class="gap-2">
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="16" />
            <native:text class="text-[15] font-semibold text-theme-secondary-text">{{ $duration }}</native:text>
        </native:row>
        <native:text class="text-[15] font-semibold text-theme-secondary-text">{{ $difficulty }}</native:text>
    </native:column>

    @if ($completionPercentage > 0)
        <native:column class="gap-2">
        <native:row class="items-center justify-between">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">PROGRESS</native:text>
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
            class="w-40"
            :label="$action"
            size="md"
            variant="primary"
            a11y-hint="Opens the workout flow preview"
            @press="openWorkout"
        />
    @endif
</native:column>
</native:column>
