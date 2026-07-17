@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$screenState" :scroll="true">
    <x-slot:error>
        <x-native.error-state title="Game checkpoint unavailable" :description="$errorMessage">
            <x-slot:retry>
                <native:button label="Return home" size="lg" variant="primary" @press="confirmExit" />
            </x-slot:retry>
        </x-native.error-state>
    </x-slot:error>

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

    <x-slot:overlays>
        <x-native.dialog-host
            :dialog-visible="$dialogVisible"
            :bottom-sheet-visible="$bottomSheetVisible"
            sheet-detents="medium,large"
            dialog-a11y-label="Exit workout confirmation"
            sheet-a11y-label="Workout pause options"
        >
            <x-slot:dialog>
                <native:column class="w-full gap-5 bg-theme-surface p-5">
                    <native:text class="text-2xl font-bold text-theme-on-surface">Leave workout?</native:text>
                    <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
                        Your current placeholder checkpoint will remain on this device so you can resume later.
                    </native:text>
                    <native:button label="Keep Training" size="lg" variant="primary" @press="cancelExit" />
                    <native:button label="Exit to Home" size="lg" variant="destructive" @press="confirmExit" />
                </native:column>
            </x-slot:dialog>
            <x-slot:sheet>
                <x-native.pause-sheet />
            </x-slot:sheet>
        </x-native.dialog-host>
    </x-slot:overlays>
</x-native.screen-container>
