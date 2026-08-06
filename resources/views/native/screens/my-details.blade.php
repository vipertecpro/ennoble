<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1" :shows-indicators="false">
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
    <x-native.ui.glow-card accent="lime-400" class="p-5">
    <native:column class="w-full gap-4" :animate-duration="$motionDuration">
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

        <native:radio-group native:model="difficulty" label="Difficulty">
            <native:radio value="beginner" label="Beginner" />
            <native:radio value="intermediate" label="Intermediate" />
            <native:radio value="advanced" label="Advanced" />
        </native:radio-group>

        @if ($this->hasUnsavedChanges())
            <x-native.ui.gradient-button
                label="Save changes"
                press="saveDetails"
            />
        @endif
    </native:column>
    </x-native.ui.glow-card>
    @endif
</native:column>
</native:scroll-view>
</native:column>
