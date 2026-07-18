@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$screenState" :scroll="true">
    @if ($screenState === 'error')
        <x-native.error-state title="Transition unavailable" :description="$errorMessage">
            <x-slot:retry>
                <native:button label="Return to workout" size="lg" variant="primary" @press="returnToWorkout" />
            </x-slot:retry>
        </x-native.error-state>
    @else
    <x-native.workout-header
        eyebrow="Between games"
        title="One step complete"
        subtitle="Take a breath before the next focus."
        :motion-duration="$motionDuration"
    />

    <x-native.workout-progress
        :current-game="'Next: '.$nextGame"
        :games-remaining="$gamesRemaining"
        :progress="$progress"
        :time-estimate="$timeEstimate"
    />

    <x-native.transition-card
        :previous-game="$previousGame"
        :next-game="$nextGame"
        :performance-message="$performanceMessage"
        :auto-transition-enabled="$autoTransitionEnabled"
        :auto-transition-seconds="$autoTransitionSeconds"
        :motion-duration="$motionDuration"
    />

    <x-native.workout-footer>
        <x-slot:primary>
            <native:button
                label="Continue"
                size="lg"
                variant="primary"
                :loading="$isTransitioning"
                :disabled="$isTransitioning"
                @press="continueWorkout"
            />
        </x-slot:primary>
    </x-native.workout-footer>
    @endif
</x-native.screen-container>
