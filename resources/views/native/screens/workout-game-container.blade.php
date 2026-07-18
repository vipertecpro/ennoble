@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$screenState" :scroll="true">
    @if ($screenState === 'error')
        <x-native.error-state title="Game checkpoint unavailable" :description="$errorMessage">
            <x-slot:retry>
                <native:button label="Return home" size="lg" variant="primary" @press="confirmExit" />
            </x-slot:retry>
        </x-native.error-state>
    @else
    <x-native.workout-header
        :eyebrow="$gameOrder"
        :title="$gameTitle"
        subtitle="Focused game container"
        :motion-duration="$motionDuration"
    >
        <x-slot:action>
            <native:button
                label="Pause"
                size="sm"
                variant="secondary"
                a11y-hint="Pauses the timer and opens workout options"
                @press="pauseWorkout"
            />
        </x-slot:action>
    </x-native.workout-header>

    <x-native.workout-progress
        :current-game="$gameTitle"
        :games-remaining="$gamesRemaining"
        :progress="$progress"
        :time-estimate="$timeEstimate"
    />

    <x-native.game-container
        :title="$gameTitle"
        :message="$placeholderMessage"
        :elapsed-time="$elapsedTime"
        :motion-duration="$motionDuration"
    >
        <native:button
            label="Complete Placeholder"
            size="lg"
            variant="secondary"
            :loading="$isSubmitting"
            :disabled="$isSubmitting || $paused"
            a11y-hint="Completes this framework step without creating gameplay evidence"
            @press="completePlaceholder"
        />
    </x-native.game-container>
    @endif

    @if ($dialogVisible)
        @include('native.partials.workout-exit-dialog')
    @endif

    @if ($bottomSheetVisible)
        @include('native.partials.workout-pause-sheet')
    @endif
</x-native.screen-container>
