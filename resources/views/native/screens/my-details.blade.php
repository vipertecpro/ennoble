<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.ui.loading-overlay label="Loading your details" />
    @elseif ($screenState === 'error')
        <x-native.ui.error-state
            :description="$screenError"
            retry-label="Retry"
            retry-method="retryMyDetails"
        />
    @else
    <native:column class="w-full items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 gap-4">
        <x-native.onboarding.display-name-input
            :display-name="$displayName"
            :overlong="! $this->isDisplayNameValid()"
            supporting="Your name never leaves this device."
        />

        <native:radio-group native:model="trainingGoal" label="Training focus">
            <native:radio value="focus" label="Focus" />
            <native:radio value="thinking_speed" label="Thinking speed" />
            <native:radio value="language" label="Communication" />
            <native:radio value="mental_sharpness" label="Mental sharpness" />
            <native:radio value="balanced" label="Balanced training" />
        </native:radio-group>

        <native:radio-group native:model="difficulty" label="Training pace">
            <native:radio value="beginner" label="Gentle" />
            <native:radio value="intermediate" label="Steady" />
            <native:radio value="advanced" label="Challenging" />
            <native:radio value="adaptive" label="Adaptive" />
        </native:radio-group>

        @if ($this->hasUnsavedChanges())
            <native:row class="justify-end">
                <native:button
                    class="w-44"
                    label="Save changes"
                    size="md"
                    :loading="$isSaving"
                    :disabled="! $this->isDisplayNameValid()"
                    a11y-hint="Saves your local details on this device"
                    @press="saveDetails"
                />
            </native:row>
        @endif
    </native:column>
    </native:column>
    @endif
</native:column>
</native:scroll-view>
</native:column>
