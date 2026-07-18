@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
    @if ($screenState === 'error')
        <x-native.error-state
            title="Transition unavailable"
            :description="$errorMessage"
            retry-label="Return to workout"
            retry-method="returnToWorkout"
        />
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

    <native:column class="w-80 items-center">
        <native:button
            class="w-56"
            label="Continue"
            size="md"
            variant="primary"
            :loading="$isTransitioning"
            :disabled="$isTransitioning"
            @press="continueWorkout"
        />
    </native:column>
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
