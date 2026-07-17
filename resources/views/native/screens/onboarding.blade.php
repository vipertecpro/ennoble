@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeComponents\Screens\Onboarding')
@use('App\NativeUI\Tokens\MotionToken')

<x-native.screen-container :safe-area="true" :scroll="$currentStep !== 1">
    <x-native.onboarding-progress
        :current-step="$currentStep"
        :total-steps="Onboarding::TOTAL_STEPS"
        :motion-duration="$this->motionDuration()"
    />

    <native:column
        class="w-full flex-1 gap-5"
        :translate-x="$reducedMotion ? 0 : ($currentStep % 2 === 0 ? 6 : 0)"
        :animate-duration="$this->motionDuration()"
        animate-easing="ease-out"
    >
        @if ($currentStep === 1)
            <native:spacer />

            <native:column class="w-full items-center gap-5">
                <x-native.onboarding-illustration
                    :ios="Ios::BrainHeadProfile"
                    :android="AndroidOutlined::Psychology"
                    a11y-label="Ennoble"
                    :animated="true"
                    :motion-duration="$this->motionDuration(MotionToken::Slow)"
                />

                <native:text class="text-4xl font-bold leading-tight text-center text-theme-on-background">
                    A clearer mind, one day at a time.
                </native:text>
                <native:text class="text-base leading-relaxed text-center text-theme-on-surface-variant">
                    Ennoble turns a few focused minutes into private, purposeful mental training.
                </native:text>
            </native:column>

            <native:spacer />
        @elseif ($currentStep === 2)
            <native:column class="w-full gap-2">
                <native:text class="text-3xl font-bold leading-tight text-theme-on-background">
                    Why Ennoble?
                </native:text>
                <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
                    Train the skills that help thought feel steadier, quicker, and more expressive.
                </native:text>
            </native:column>

            <x-native.onboarding-feature-carousel :motion-duration="$this->motionDuration()" />
        @elseif ($currentStep === 3)
            <native:column class="w-full items-center gap-4">
                <x-native.onboarding-illustration
                    :ios="Ios::LockShield"
                    :android="AndroidOutlined::PrivacyTip"
                    a11y-label="Private offline training"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
                <native:text class="text-3xl font-bold leading-tight text-center text-theme-on-background">
                    Training built around you.
                </native:text>
                <native:text class="text-base leading-relaxed text-center text-theme-on-surface-variant">
                    Small daily improvements matter more than long, exhausting sessions.
                </native:text>
            </native:column>

            <native:column class="w-full gap-1 rounded-3xl bg-theme-surface p-5">
                <native:row class="w-full items-center gap-3 py-3">
                    <x-native.icon :ios="Ios::WifiSlash" :android="AndroidOutlined::WifiOff" :size="24" />
                    <native:text class="flex-1 text-base text-theme-on-surface">Works fully offline</native:text>
                </native:row>
                <native:divider />
                <native:row class="w-full items-center gap-3 py-3">
                    <x-native.icon :ios="Ios::ShieldLefthalfFilled" :android="AndroidOutlined::Shield" :size="24" />
                    <native:text class="flex-1 text-base text-theme-on-surface">Private by design</native:text>
                </native:row>
                <native:divider />
                <native:row class="w-full items-center gap-3 py-3">
                    <x-native.icon :ios="Ios::PersonCropCircle" :android="AndroidOutlined::Person" :size="24" />
                    <native:text class="flex-1 text-base text-theme-on-surface">No account required</native:text>
                </native:row>
                <native:divider />
                <native:row class="w-full items-center gap-3 py-3">
                    <x-native.icon :ios="Ios::Sparkles" :android="AndroidOutlined::AutoAwesome" :size="24" />
                    <native:text class="flex-1 text-base text-theme-on-surface">No advertisements</native:text>
                </native:row>
                <native:divider />
                <native:row class="w-full items-center gap-3 py-3">
                    <x-native.icon :ios="Ios::LockShield" :android="AndroidOutlined::Lock" :size="24" />
                    <native:text class="flex-1 text-base text-theme-on-surface">Everything stays on this device</native:text>
                </native:row>
            </native:column>
        @elseif ($currentStep === 4)
            <native:column class="w-full gap-2">
                <native:text class="text-3xl font-bold leading-tight text-theme-on-background">
                    What would you like to strengthen?
                </native:text>
                <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
                    Choose one starting goal. You can refine it later.
                </native:text>
            </native:column>

            <native:column class="w-full rounded-3xl bg-theme-surface p-5">
                <native:radio-group
                    native:model="trainingGoal"
                    label="Training goal"
                    a11y-label="Choose one training goal"
                >
                    <native:radio value="focus" label="Improve Focus" />
                    <native:radio value="thinking_speed" label="Improve Thinking Speed" />
                    <native:radio value="language" label="Improve Communication" />
                    <native:radio value="mental_sharpness" label="Stay Mentally Sharp" />
                    <native:radio value="balanced" label="General Improvement" />
                </native:radio-group>
            </native:column>
        @elseif ($currentStep === 5)
            <native:column class="w-full items-center gap-4">
                <x-native.onboarding-illustration
                    :ios="Ios::GaugeOpenWithLinesNeedle33percent"
                    :android="AndroidOutlined::Speed"
                    a11y-label="Training difficulty"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
                <native:text class="text-3xl font-bold leading-tight text-center text-theme-on-background">
                    Choose your starting pace.
                </native:text>
                <native:text class="text-base leading-relaxed text-center text-theme-on-surface-variant">
                    Adaptive starts steady and responds to your future training evidence.
                </native:text>
            </native:column>

            <native:column class="w-full rounded-3xl bg-theme-surface p-5">
                <native:radio-group
                    native:model="difficulty"
                    label="Difficulty"
                    a11y-label="Choose one starting difficulty"
                >
                    <native:radio value="beginner" label="Beginner" />
                    <native:radio value="intermediate" label="Intermediate" />
                    <native:radio value="advanced" label="Advanced" />
                    <native:radio value="adaptive" label="Adaptive" />
                </native:radio-group>
            </native:column>
        @elseif ($currentStep === 6)
            <native:column class="w-full items-center gap-4">
                <x-native.onboarding-illustration
                    :ios="Ios::PersonCropCircle"
                    :android="AndroidOutlined::Person"
                    a11y-label="Local profile"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
                <native:text class="text-3xl font-bold leading-tight text-center text-theme-on-background">
                    What should Ennoble call you?
                </native:text>
                <native:text class="text-base leading-relaxed text-center text-theme-on-surface-variant">
                    This is optional. Your profile remains only on this device.
                </native:text>
            </native:column>

            <x-native.onboarding-display-name-input
                :display-name="$displayName"
                :valid="$this->isDisplayNameValid()"
            />
        @elseif ($currentStep === 7)
            <native:column class="w-full gap-2">
                <native:text class="text-3xl font-bold leading-tight text-theme-on-background">
                    Make Ennoble comfortable.
                </native:text>
                <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
                    These preferences apply locally and can be changed later.
                </native:text>
            </native:column>

            <native:column class="w-full gap-4 rounded-3xl bg-theme-surface p-5">
                <native:radio-group
                    native:model="themePreference"
                    label="Theme"
                    a11y-label="Choose an appearance theme"
                >
                    <native:radio value="system" label="Use Device Setting" />
                    <native:radio value="light" label="Light" />
                    <native:radio value="dark" label="Dark" />
                </native:radio-group>

                <native:divider />

                <native:toggle
                    native:model="soundEnabled"
                    label="Sound"
                    a11y-label="Sound feedback"
                />
                <native:toggle
                    native:model="hapticsEnabled"
                    label="Haptics"
                    a11y-label="Haptic feedback"
                />
                <native:toggle
                    native:model="reducedMotion"
                    label="Reduced Motion"
                    a11y-label="Reduced motion"
                    a11y-hint="Reduces non-essential movement throughout Ennoble"
                />
            </native:column>
        @else
            <native:column class="w-full items-center gap-4">
                <x-native.onboarding-illustration
                    :ios="Ios::CheckmarkSeal"
                    :android="AndroidOutlined::Verified"
                    a11y-label="Onboarding ready"
                    :animated="true"
                    :motion-duration="$this->motionDuration(MotionToken::Success)"
                    compact
                />
                <native:text class="text-3xl font-bold leading-tight text-center text-theme-on-background">
                    Your training space is ready.
                </native:text>
                <native:text class="text-base leading-relaxed text-center text-theme-on-surface-variant">
                    A short daily rhythm is enough to begin.
                </native:text>
            </native:column>

            <native:column class="w-full rounded-3xl bg-theme-surface px-5 py-2">
                <x-native.onboarding-summary-row label="Goal" :value="$this->trainingGoalLabel()" />
                <native:divider />
                <x-native.onboarding-summary-row label="Difficulty" :value="$this->difficultyLabel()" />
                <native:divider />
                <x-native.onboarding-summary-row label="Theme" :value="$this->themeLabel()" />
                <native:divider />
                <x-native.onboarding-summary-row label="Display name" :value="$this->displayNameSummary()" />
                <native:divider />
                <x-native.onboarding-summary-row label="Daily training" value="5–10 minutes" />
            </native:column>

            <native:text class="text-sm leading-relaxed text-center text-theme-on-surface-variant">
                No account will be created. Your choices stay on this device.
            </native:text>
        @endif
    </native:column>

    @if ($errorMessage)
        <native:column class="w-full rounded-2xl bg-theme-surface-variant p-4">
            <native:text class="text-sm font-semibold text-theme-destructive">
                {{ $errorMessage }}
            </native:text>
        </native:column>
    @endif

    <native:row class="w-full items-center gap-3">
        @if ($currentStep > 1)
            <native:button
                class="flex-1"
                label="Back"
                variant="secondary"
                :disabled="$isSaving"
                @press="previousStep"
            />
        @endif

        @if ($currentStep < Onboarding::TOTAL_STEPS)
            <native:button
                class="flex-1"
                :label="$currentStep === 1 ? 'Begin' : 'Continue'"
                size="lg"
                :disabled="! $this->canContinue()"
                @press="nextStep"
            />
        @else
            <native:button
                class="flex-1"
                label="Start Training"
                size="lg"
                :loading="$isSaving"
                :disabled="! $this->canContinue()"
                a11y-hint="Saves your local choices and opens Ennoble"
                @press="completeOnboarding"
            />
        @endif
    </native:row>
</x-native.screen-container>
