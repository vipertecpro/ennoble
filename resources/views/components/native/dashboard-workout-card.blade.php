@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\Enums\WorkoutStatus')

@props([
    'title',
    'duration',
    'skills' => [],
    'difficulty',
    'action',
    'status',
    'completionPercentage' => 0,
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-5 rounded-3xl bg-theme-primary p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $title }}, {{ $completionPercentage }} percent complete"
>
    <native:row class="w-full items-center gap-4">
        <native:column class="flex-1 gap-1">
            <native:text class="text-xs font-semibold text-theme-on-primary">TODAY’S TRAINING</native:text>
            <native:text class="text-2xl font-bold leading-tight text-theme-on-primary">{{ $title }}</native:text>
        </native:column>

        <native:column class="items-center justify-center rounded-2xl bg-theme-secondary p-3">
            <x-native.icon
                :ios="$status === WorkoutStatus::Completed->value ? Ios::Checkmark : Ios::Brain"
                :android="$status === WorkoutStatus::Completed->value ? AndroidOutlined::Check : AndroidOutlined::Psychology"
                :size="28"
                :a11y-label="$status === WorkoutStatus::Completed->value ? 'Workout completed' : 'Brain training'"
            />
        </native:column>
    </native:row>

    <native:row class="w-full items-center gap-4">
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="18" />
            <native:text class="text-sm font-semibold text-theme-on-primary">{{ $duration }}</native:text>
        </native:row>
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Gauge" :android="AndroidOutlined::Speed" :size="18" />
            <native:text class="text-sm font-semibold text-theme-on-primary">{{ $difficulty }}</native:text>
        </native:row>
    </native:row>

    <native:column class="w-full gap-2">
        <native:text class="text-xs font-semibold text-theme-on-primary">SKILLS INCLUDED</native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-primary">
            {{ implode(' · ', $skills) }}
        </native:text>
    </native:column>

    <native:column class="w-full gap-2">
        <native:row class="w-full items-center justify-between">
            <native:text class="text-xs font-semibold text-theme-on-primary">TODAY’S PROGRESS</native:text>
            <native:text class="text-xs font-semibold text-theme-on-primary">{{ $completionPercentage }}%</native:text>
        </native:row>
        <native:progress-bar
            :value="$completionPercentage / 100"
            color="white"
            track-color="white/30"
            a11y-label="Today’s workout is {{ $completionPercentage }} percent complete"
        />
    </native:column>

    <native:button
        :label="$action"
        size="lg"
        variant="secondary"
        :disabled="$status === WorkoutStatus::Completed->value"
        a11y-hint="{{ $status === WorkoutStatus::Completed->value ? 'Today’s workout is complete' : 'Opens the workout flow preview' }}"
        @press="openWorkout"
    />
</native:column>
