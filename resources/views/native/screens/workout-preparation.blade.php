<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-full px-4 mb-12 mt-7 gap-6">
    @if ($screenState === 'error')
        <x-native.error-state
            title="Preparation unavailable"
            :description="$errorMessage"
            retry-label="Return to workout"
            retry-method="returnToWorkout"
        />
    @else
        <x-native.workout-header
            :eyebrow="$gameOrder"
            title="Settle into focus."
            :subtitle="'Up next: '.$gameTitle"
            :motion-duration="$motionDuration"
        />

        <x-native.workout-progress
            :current-game="$gameTitle"
            :games-remaining="$gamesRemaining"
            :progress="$progress"
            :time-estimate="$timeEstimate"
            :steps="$journeySteps"
        />

        <x-native.countdown
            :count="$countdown"
            :announcement="$countdownAnnouncement"
            :coaching="$coaching"
            :motion-duration="$motionDuration"
        />

        <native:text class="text-center text-[15] leading-relaxed text-theme-secondary-text">
            {{ $instructions }}
        </native:text>

        <native:column class="w-full items-center gap-3">
            <native:button class="w-56" :label="'Enter '.$gameTitle" size="lg" variant="primary" @press="startGame" />
            <native:button class="w-56" label="Leave workout" size="md" variant="ghost" @press="returnToWorkout" />
        </native:column>
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
