@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
    @if ($screenState === 'error')
        <x-native.error-state
            title="Summary unavailable"
            :description="$errorMessage"
            retry-label="Return home"
            retry-method="continueHome"
        />
    @else
    <x-native.workout-header
        eyebrow="Daily Momentum"
        title="A focused finish"
        subtitle="Your framework workout is complete."
        :motion-duration="$motionDuration"
    />

    <x-native.workout-progress
        current-game="Workout complete"
        :games-remaining="0"
        :progress="1"
        time-estimate="Finished"
    />

    <x-native.completion-card
        :duration="$duration"
        :games-completed="$gamesCompleted"
        :skills="$skills"
        :score-summary="$scoreSummary"
        :accuracy-summary="$accuracySummary"
        :progress-message="$progressMessage"
        :motion-duration="$motionDuration"
    />

    <native:column class="w-80 items-center">
        <native:button class="w-56" label="Continue to Home" size="md" variant="primary" @press="continueHome" />
    </native:column>
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
