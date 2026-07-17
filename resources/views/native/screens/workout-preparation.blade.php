@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$screenState" :scroll="true">
    <x-slot:error>
        <x-native.error-state title="Preparation unavailable" :description="$errorMessage">
            <x-slot:retry>
                <native:button label="Return to workout" size="lg" variant="primary" @press="returnToWorkout" />
            </x-slot:retry>
        </x-native.error-state>
    </x-slot:error>

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

    <native:column class="w-full gap-2 rounded-2xl bg-theme-surface p-4">
        <native:text class="text-xs font-semibold text-theme-primary">HOW TO APPROACH THIS GAME</native:text>
        <native:text class="text-base leading-relaxed text-theme-on-surface">{{ $instructions }}</native:text>
    </native:column>

    <x-native.countdown
        :count="$countdown"
        :announcement="$countdownAnnouncement"
        :motion-duration="$motionDuration"
    />

    <x-native.workout-footer>
        <x-slot:primary>
            <native:button label="Start Now" size="lg" variant="primary" @press="startGame" />
        </x-slot:primary>
        <x-slot:secondary>
            <native:button label="Back to Workout" size="lg" variant="secondary" @press="returnToWorkout" />
        </x-slot:secondary>
    </x-native.workout-footer>
</x-native.screen-container>
