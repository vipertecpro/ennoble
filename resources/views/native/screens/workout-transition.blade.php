<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mb-12 mt-7 gap-5">
    @if ($screenState === 'error')
        <x-native.error-state
            title="Transition unavailable"
            :description="$errorMessage"
            retry-label="Return to workout"
            retry-method="returnToWorkout"
        />
    @else
        <x-native.workout-progress
            :current-game="$isFinalGame ? 'Daily rhythm complete' : 'Next: '.$nextGame"
            :games-remaining="$gamesRemaining"
            :progress="$progress"
            :time-estimate="$timeEstimate"
            :steps="$journeySteps"
        />

        <x-native.transition-card
            :previous-game="$previousGame"
            :next-game="$nextGame"
            :performance-message="$performanceMessage"
            :coaching="$coaching"
            :coaching-detail="$coachingDetail"
            :next-prompt="$nextPrompt"
            :auto-transition-enabled="$autoTransitionEnabled"
            :auto-transition-seconds="$autoTransitionSeconds"
            :is-final-game="$isFinalGame"
            :motion-duration="$motionDuration"
        />

        <native:column class="w-full items-center">
            <native:button
                class="w-56"
                :label="$isFinalGame ? 'See results now' : 'Start next now'"
                size="md"
                variant="ghost"
                :loading="$isTransitioning"
                :disabled="$isTransitioning"
                @press="continueWorkout"
            />
        </native:column>
    @endif
</native:column>
</native:scroll-view>
</native:column>
