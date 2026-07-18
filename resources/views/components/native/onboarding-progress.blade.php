@props([
    'currentStep',
    'totalSteps',
    'motionDuration' => 0,
])

<native:row class="items-center justify-between">
    <native:text class="text-xs font-semibold text-theme-secondary-text">
            Step {{ $currentStep }} of {{ $totalSteps }}
    </native:text>

    <native:row class="items-center gap-2" a11y-label="Onboarding progress, step {{ $currentStep }} of {{ $totalSteps }}">
        @for ($step = 1; $step <= $totalSteps; $step++)
            <native:column
                class="{{ $step === $currentStep ? 'w-5' : 'w-2' }} h-2 rounded-full {{ $step <= $currentStep ? 'bg-theme-accent' : 'bg-theme-divider' }}"
                :scale="$step <= $currentStep ? 1 : 0.78"
                :opacity="$step <= $currentStep ? 1 : 0.62"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            />
        @endfor
    </native:row>
</native:row>
