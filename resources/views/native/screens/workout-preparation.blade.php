@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
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
        :title="$gameTitle"
        subtitle="Review the focus, then follow the countdown."
        :motion-duration="$motionDuration"
    />

    <x-native.workout-progress
        :current-game="$gameTitle"
        :games-remaining="$gamesRemaining"
        :progress="$progress"
        :time-estimate="$timeEstimate"
    />

    <native:column class="w-80 items-center rounded-3xl border border-theme-border bg-theme-surface-elevated py-5">
    <native:column class="w-72 gap-4">
        <native:text class="text-xs font-semibold text-theme-accent">HOW TO APPROACH THIS GAME</native:text>
        <native:text class="text-base leading-relaxed text-theme-primary-text">{{ $instructions }}</native:text>
    </native:column>
    </native:column>

    <x-native.countdown
        :count="$countdown"
        :announcement="$countdownAnnouncement"
        :motion-duration="$motionDuration"
    />

    <native:column class="w-80 items-center gap-3">
        <native:button class="w-56" label="Start Now" size="md" variant="primary" @press="startGame" />
        <native:button class="w-56" label="Back to Workout" size="md" variant="ghost" @press="returnToWorkout" />
    </native:column>
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
