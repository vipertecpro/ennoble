@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.loading-overlay label="Loading your profile" />
    @elseif ($screenState === 'error')
        <x-native.error-state
            :description="$screenError"
            retry-label="Retry profile"
            retry-method="retryProfile"
        />
    @else
    <native:column class="w-80 items-center rounded-2xl bg-theme-primary-surface py-6" :animate-duration="$motionDuration">
    <native:column class="w-72 items-center gap-4">
        <native:column class="w-20 h-20 items-center justify-center rounded-full bg-theme-surface-elevated shadow-sm">
            @if ($monogram !== '')
                <native:text class="text-[28] font-bold tracking-tight text-theme-primary-text">{{ $monogram }}</native:text>
            @else
                <x-native.icon
                    :ios="Ios::Person"
                    :android="AndroidOutlined::Person"
                    :size="32"
                    a11y-label="Local profile"
                />
            @endif
        </native:column>

        <native:column class="items-center gap-1">
            <native:text class="text-[22] font-semibold tracking-tight leading-tight text-center text-theme-primary-text">{{ $identityName }}</native:text>
            <native:text class="text-[15] text-theme-muted-text">{{ $memberSince }}</native:text>
        </native:column>

        <native:text class="text-[15] font-semibold text-theme-secondary-text">
            {{ $goalLabel }} · {{ $paceLabel }}
        </native:text>
    </native:column>
    </native:column>

    <x-native.dashboard-section-header title="Your practice" />

    <native:column class="w-80 items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
    <native:column class="w-72 gap-3">
        <native:row class="gap-3">
            <x-native.game-stat
                :ios="Ios::CheckmarkSeal"
                :android="AndroidOutlined::Verified"
                label="Workouts"
                :value="$workoutsLabel"
            />
            <x-native.game-stat
                :ios="Ios::Flame"
                :android="AndroidOutlined::LocalFireDepartment"
                label="Day streak"
                :value="$streakLabel"
            />
        </native:row>
        <native:row class="gap-3">
            <x-native.game-stat
                :ios="Ios::Rosette"
                :android="AndroidOutlined::MilitaryTech"
                label="Achievements"
                :value="$achievementsLabel"
            />
        </native:row>
    </native:column>
    </native:column>

    <x-native.dashboard-section-header title="Your details" />

    <native:column class="w-80 items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
    <native:column class="w-72 gap-4">
        <x-native.onboarding-display-name-input
            :display-name="$displayName"
            :valid="$this->isDisplayNameValid()"
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

    <x-native.dashboard-section-header title="More" />

    <native:column class="rounded-2xl bg-theme-surface shadow-sm">
        <x-native.settings-link-row
            label="Settings"
            description="Appearance, feedback, and motion."
            method="openSettings"
            :ios="Ios::Gearshape"
            :android="AndroidOutlined::Settings"
        />
        <native:divider />
        <x-native.settings-link-row
            label="About Ennoble"
            description="A private daily practice for a clearer mind."
            method="openAbout"
            :ios="Ios::Info"
            :android="AndroidOutlined::Info"
        />
    </native:column>
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
