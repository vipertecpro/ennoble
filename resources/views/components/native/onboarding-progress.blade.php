@props([
    'currentStep',
    'totalSteps',
    'motionDuration' => 0,
])

@php
    $progress = max(0, min(1, $currentStep / $totalSteps));
@endphp

<native:column class="w-full gap-3">
    <native:row class="w-full items-center justify-between">
        <native:text class="text-sm font-semibold text-theme-on-background">
            Step {{ $currentStep }} of {{ $totalSteps }}
        </native:text>
        <native:text class="text-sm text-theme-on-surface-variant">
            {{ (int) round($progress * 100) }}%
        </native:text>
    </native:row>

    <native:progress-bar
        :value="$progress"
        a11y-label="Onboarding progress, step {{ $currentStep }} of {{ $totalSteps }}"
    />

    <native:row class="w-full items-center gap-2" a11y-label="{{ $currentStep - 1 }} onboarding steps completed">
        @for ($step = 1; $step <= $totalSteps; $step++)
            <native:column
                class="h-1 flex-1 rounded-full {{ $step <= $currentStep ? 'bg-theme-primary' : 'bg-theme-surface-variant' }}"
                :scale="$step <= $currentStep ? 1 : 0.72"
                :opacity="$step <= $currentStep ? 1 : 0.62"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            />
        @endfor
    </native:row>
</native:column>
