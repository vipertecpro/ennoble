@props([
    'currentStep',
    'totalSteps',
    'motionDuration' => 0,
])

{{-- Explicit w-full so the label and dots pin to the shared px-4 gutters —
     without it the row hugs its content and justify-between collapses. --}}
<native:row class="w-full items-center justify-between">
    <native:text class="text-[13] font-semibold text-theme-secondary-text">
        Step {{ $currentStep }} of {{ $totalSteps }}
    </native:text>

    {{-- One lime moment: only the ACTIVE pill carries the accent. Completed
         dots settle to secondary ink, upcoming dots stay muted — the rail
         reads as progress without competing with the screen's CTA. --}}
    <native:row class="items-center gap-2" a11y-label="Onboarding progress, step {{ $currentStep }} of {{ $totalSteps }}">
        @for ($step = 1; $step <= $totalSteps; $step++)
            <native:column
                class="{{ $step === $currentStep ? 'w-5 bg-theme-accent' : 'w-2' }} h-2 rounded-full {{ $step < $currentStep ? 'bg-theme-secondary-text' : '' }} {{ $step > $currentStep ? 'bg-theme-muted-text' : '' }}"
                :scale="$step <= $currentStep ? 1 : 0.78"
                :opacity="$step <= $currentStep ? 1 : 0.62"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            />
        @endfor
    </native:row>
</native:row>
