@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
    @if ($screenState === 'error')
        <x-native.error-state
            title="Game checkpoint unavailable"
            :description="$errorMessage"
            retry-label="Return home"
            retry-method="confirmExit"
        />
    @else
    <x-native.workout-header
        :eyebrow="$gameOrder"
        :title="$gameTitle"
        subtitle="Focused game container"
        :motion-duration="$motionDuration"
    />

    <native:row class="w-80 justify-end">
        <native:button
            class="w-28"
            label="Pause"
            size="sm"
            variant="secondary"
            a11y-hint="Pauses the timer and opens workout options"
            @press="pauseWorkout"
        />
    </native:row>

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
        action-label="Complete Placeholder"
        action-method="completePlaceholder"
        :action-loading="$isSubmitting"
        :action-disabled="$isSubmitting || $paused"
    />
    @endif

    @if ($dialogVisible)
        @include('native.partials.workout-exit-dialog')
    @endif

    @if ($bottomSheetVisible)
        @include('native.partials.workout-pause-sheet')
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
