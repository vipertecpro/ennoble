@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$screenState" :scroll="true">
    <x-slot:error>
        <x-native.error-state title="Summary unavailable" :description="$errorMessage">
            <x-slot:retry>
                <native:button label="Return home" size="lg" variant="primary" @press="continueHome" />
            </x-slot:retry>
        </x-native.error-state>
    </x-slot:error>

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
        :motion-duration="$motionDuration"
    />

    <x-native.workout-footer>
        <x-slot:primary>
            <native:button label="Continue to Home" size="lg" variant="primary" @press="continueHome" />
        </x-slot:primary>
    </x-native.workout-footer>
</x-native.screen-container>
