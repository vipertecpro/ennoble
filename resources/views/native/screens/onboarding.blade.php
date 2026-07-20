@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeComponents\Screens\Onboarding')
@use('App\NativeUI\Tokens\MotionToken')

{{--
    Every band of this screen — progress rail, step content, footer actions —
    is a COLUMN spanning the full width with the same symmetric px-4 screen
    gutters used by the rest of Ennoble, so edges align across bands on every
    device.

    IMPORTANT: never wrap a band in `<native:row justify-center>`. On iOS the
    flex engine measures a row's children with an UNBOUNDED width proposal
    (FlexContainer Phase A proposes width:nil along the row main axis), and a
    `w-full` child has no ideal width — so it collapses to its natural content
    width: text stops wrapping and clips off-screen, and w-full buttons render
    as compact pills. Columns propose finite cross-axis width, so a `w-full
    px-4` column as a DIRECT child of a column/scroll-view is the only correct
    rail pattern.
--}}
<native:column class="h-full w-full bg-theme-background {{ $this->appliesManualSafeArea() ? 'safe-area' : '' }}">

    {{-- Fixed header — progress rail stays put while content scrolls --}}
    <native:column class="w-full px-4 pt-2 pb-3 bg-theme-background">
        <x-native.onboarding.progress
            :current-step="$currentStep"
            :total-steps="Onboarding::TOTAL_STEPS"
            :motion-duration="$this->motionDuration()"
        />
    </native:column>

    @if ($currentStep === 1)
        {{-- Welcome hero — short content, no scroll: vertically centered
             between the progress rail and the footer for a calm, symmetric
             opening moment. --}}
        <native:column
            native:key="onboarding-step-1"
            class="w-full flex-1 px-4 pb-6 items-center justify-center gap-6"
            :translate-x="$reducedMotion ? 0 : 8"
            :opacity="$reducedMotion ? 1 : 0.98"
            :animate-duration="$this->motionDuration()"
            animate-easing="ease-out"
        >
            <x-native.onboarding.illustration
                :ios="Ios::BrainHeadProfile"
                :android="AndroidOutlined::Psychology"
                a11y-label="Ennoble brain training"
                :animated="true"
                :motion-duration="$this->motionDuration(MotionToken::Slow)"
            />

            <native:column class="w-full items-center gap-2">
                <native:text class="w-full text-[26] font-bold tracking-tight leading-tight text-center text-theme-primary-text">
                    Train a sharper mind.
                </native:text>
                <native:text class="w-full text-[15] leading-relaxed text-center text-theme-secondary-text">
                    Short, private exercises designed for calm daily progress.
                </native:text>
            </native:column>
        </native:column>
    @else
    {{-- Scrollable step content --}}
    <native:scroll-view class="flex-1 bg-theme-background" :shows-indicators="false">
    <native:column
        native:key="onboarding-step-{{ $currentStep }}"
        class="w-full px-4 pt-4 pb-6 gap-6"
        :translate-x="$reducedMotion ? 0 : 8"
        :opacity="$reducedMotion ? 1 : 0.98"
        :animate-duration="$this->motionDuration()"
        animate-easing="ease-out"
    >
        @if ($currentStep === 2)
            <native:column class="w-full items-center">
                <x-native.onboarding.illustration
                    :ios="Ios::Scope"
                    :android="AndroidOutlined::CenterFocusStrong"
                    a11y-label="Choose a training focus"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
            </native:column>

            <native:column class="w-full gap-2">
                <native:text class="w-full text-[22] font-bold tracking-tight leading-tight text-theme-primary-text">
                    What should we train first?
                </native:text>
                <native:text class="w-full text-[13] leading-relaxed text-theme-secondary-text">
                    Pick one focus. You can change it later.
                </native:text>
            </native:column>

            <native:column class="w-full gap-4">
                <native:radio-group native:model="trainingGoal" label="Training focus">
                    <native:radio value="focus" label="Focus" />
                    <native:radio value="thinking_speed" label="Thinking speed" />
                    <native:radio value="language" label="Communication" />
                    <native:radio value="mental_sharpness" label="Mental sharpness" />
                    <native:radio value="balanced" label="Balanced training" />
                </native:radio-group>
            </native:column>
        @elseif ($currentStep === 3)
            <native:column class="w-full items-center">
                <x-native.onboarding.illustration
                    :ios="Ios::GaugeOpenWithLinesNeedle33percent"
                    :android="AndroidOutlined::Speed"
                    a11y-label="Choose a training pace"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
            </native:column>

            <native:column class="w-full gap-2">
                <native:text class="w-full text-[22] font-bold tracking-tight leading-tight text-theme-primary-text">
                    Choose your pace.
                </native:text>
                <native:text class="w-full text-[13] leading-relaxed text-theme-secondary-text">
                    Start comfortably. Ennoble will adapt with you.
                </native:text>
            </native:column>

            <native:column class="w-full gap-4">
                <native:radio-group native:model="difficulty" label="Training pace">
                    <native:radio value="beginner" label="Gentle" />
                    <native:radio value="intermediate" label="Steady" />
                    <native:radio value="advanced" label="Challenging" />
                    <native:radio value="adaptive" label="Adaptive" />
                </native:radio-group>
            </native:column>
        @elseif ($currentStep === 4)
            <native:column class="w-full items-center">
                <x-native.onboarding.illustration
                    :ios="Ios::PersonCropCircle"
                    :android="AndroidOutlined::Person"
                    a11y-label="Local profile"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
            </native:column>

            <native:column class="w-full gap-2">
                <native:text class="w-full text-[22] font-bold tracking-tight leading-tight text-theme-primary-text">
                    What should we call you?
                </native:text>
                <native:text class="w-full text-[13] leading-relaxed text-theme-secondary-text">
                    Your name stays on this device.
                </native:text>
            </native:column>

            <native:column class="w-full">
                <x-native.onboarding.display-name-input
                    :display-name="$displayName"
                    :overlong="$this->isDisplayNameTooLong()"
                    a11y-hint="Required to continue. Stays on this device."
                />
            </native:column>
        @elseif ($currentStep === 5)
            {{-- No hero badge on this step: it is the densest of the six, and
                 with the badge the content slightly overflowed the scroll
                 viewport, clipping the halo mid-circle. Dropping the decoration
                 lets the whole step sit in view without scrolling. --}}
            <native:column class="w-full gap-2 pt-2">
                <native:text class="w-full text-[22] font-bold tracking-tight leading-tight text-theme-primary-text">
                    Make it feel right.
                </native:text>
                <native:text class="w-full text-[13] leading-relaxed text-theme-secondary-text">
                    Set your feedback preferences.
                </native:text>
            </native:column>

            {{-- Sound and haptics grouped under a Cortex ALL-CAPS section
                 label. Appearance is not offered here: the app follows the
                 device's Light/Dark setting so every surface stays coherent. --}}
            <native:column class="w-full gap-2">
                <native:text class="w-full text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
                    Feedback
                </native:text>
                <native:column class="w-full gap-3 rounded-2xl bg-theme-surface shadow-sm px-4 py-2">
                    <native:toggle native:model="soundEnabled" label="Sound" />
                    <native:divider />
                    <native:toggle native:model="hapticsEnabled" label="Haptics" />
                </native:column>
            </native:column>
        @else
            <native:column class="w-full items-center gap-6">
                <x-native.onboarding.illustration
                    :ios="Ios::CheckmarkSeal"
                    :android="AndroidOutlined::Verified"
                    a11y-label="Training setup complete"
                    :animated="true"
                    :motion-duration="$this->motionDuration(MotionToken::Success)"
                />

                <native:column class="w-full items-center gap-2">
                    <native:text class="w-full text-[22] font-bold tracking-tight leading-tight text-center text-theme-primary-text">
                        Ready for day one.
                    </native:text>
                    <native:text class="w-full text-[13] leading-relaxed text-center text-theme-secondary-text">
                        Your private training space is ready.
                    </native:text>
                </native:column>
            </native:column>

            <native:column class="w-full gap-1 rounded-2xl bg-theme-surface shadow-sm px-4 py-2">
                <x-native.onboarding.summary-row label="Name" :value="$this->displayNameSummary()" />
                <native:divider />
                <x-native.onboarding.summary-row label="Focus" :value="$this->trainingGoalLabel()" />
                <native:divider />
                <x-native.onboarding.summary-row label="Pace" :value="$this->paceLabel()" />
            </native:column>
        @endif
    </native:column>
    </native:scroll-view>
    @endif

    {{-- Fixed footer — one full-width primary action, a quiet Back beneath it,
         inside the same px-4 screen gutters as the content above. --}}
    <native:column ref="onboarding-actions" class="w-full px-4 pt-3 pb-8 gap-2 bg-theme-background">
        @if ($errorMessage)
            <native:column class="w-full rounded-2xl bg-theme-secondary-surface p-4">
                <native:text class="w-full text-[13] font-semibold text-theme-danger">
                    {{ $errorMessage }}
                </native:text>
            </native:column>
        @endif

        @if ($currentStep < Onboarding::TOTAL_STEPS)
            <native:button
                class="w-full"
                :label="$currentStep === 1 ? 'Get started' : 'Continue'"
                size="lg"
                :disabled="! $this->canContinue()"
                @press="nextStep"
            />
        @else
            <native:button
                class="w-full"
                label="Start training"
                size="lg"
                :loading="$isSaving"
                :disabled="! $this->canContinue()"
                a11y-hint="Saves your local choices and opens Ennoble"
                @press="completeOnboarding"
            />
        @endif

        @if ($currentStep > 1)
            <native:button
                class="w-full"
                label="Back"
                size="md"
                variant="ghost"
                :disabled="$isSaving"
                @press="previousStep"
            />
        @endif
    </native:column>

</native:column>
