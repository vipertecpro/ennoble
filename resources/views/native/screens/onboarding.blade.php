@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeComponents\Screens\Onboarding')
@use('App\NativeUI\Tokens\MotionToken')

<native:column class="h-full w-full bg-theme-background">

    {{-- Fixed header — progress rail stays put while content scrolls --}}
    <native:row class="w-full justify-center bg-theme-background px-5 pt-5 pb-2">
        <native:column class="w-80">
            <x-native.onboarding-progress
                :current-step="$currentStep"
                :total-steps="Onboarding::TOTAL_STEPS"
                :motion-duration="$this->motionDuration()"
            />
        </native:column>
    </native:row>

    {{-- Scrollable step content --}}
    <native:scroll-view class="flex-1 bg-theme-background" :shows-indicators="false">
    <native:row class="w-full justify-center bg-theme-background">
    <native:column
        native:key="onboarding-step-{{ $currentStep }}"
        class="w-80 pt-6 pb-8 gap-7"
        :translate-x="$reducedMotion ? 0 : 8"
        :opacity="$reducedMotion ? 1 : 0.98"
        :animate-duration="$this->motionDuration()"
        animate-easing="ease-out"
    >
        @if ($currentStep === 1)
            <native:column class="w-80 items-center gap-7">
                <x-native.onboarding-illustration
                    :ios="Ios::BrainHeadProfile"
                    :android="AndroidOutlined::Psychology"
                    a11y-label="Ennoble brain training"
                    :animated="true"
                    :motion-duration="$this->motionDuration(MotionToken::Slow)"
                />

                <native:column class="items-center gap-3">
                    <native:text class="text-[34] font-bold tracking-tight leading-tight text-center text-theme-primary-text">
                        Train a sharper mind.
                    </native:text>
                    <native:text class="w-72 text-[17] leading-relaxed text-center text-theme-secondary-text">
                        Short, private exercises designed for calm daily progress.
                    </native:text>
                </native:column>
            </native:column>
        @elseif ($currentStep === 2)
            <native:column class="w-80 items-center">
                <x-native.onboarding-illustration
                    :ios="Ios::Scope"
                    :android="AndroidOutlined::CenterFocusStrong"
                    a11y-label="Choose a training focus"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
            </native:column>

            <native:column class="gap-2">
                <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">
                    What should we train first?
                </native:text>
                <native:text class="w-72 text-[15] leading-relaxed text-theme-secondary-text">
                    Pick one focus. You can change it later.
                </native:text>
            </native:column>

            <native:column class="w-80 items-center">
            <native:column class="w-72 gap-4">
                <native:radio-group native:model="trainingGoal" label="Training focus">
                    <native:radio value="focus" label="Focus" />
                    <native:radio value="thinking_speed" label="Thinking speed" />
                    <native:radio value="language" label="Communication" />
                    <native:radio value="mental_sharpness" label="Mental sharpness" />
                    <native:radio value="balanced" label="Balanced training" />
                </native:radio-group>
            </native:column>
            </native:column>
        @elseif ($currentStep === 3)
            <native:column class="w-80 items-center">
                <x-native.onboarding-illustration
                    :ios="Ios::GaugeOpenWithLinesNeedle33percent"
                    :android="AndroidOutlined::Speed"
                    a11y-label="Choose a training pace"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
            </native:column>

            <native:column class="gap-2">
                <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">
                    Choose your pace.
                </native:text>
                <native:text class="w-72 text-[15] leading-relaxed text-theme-secondary-text">
                    Start comfortably. Ennoble will adapt with you.
                </native:text>
            </native:column>

            <native:column class="w-80 items-center">
            <native:column class="w-72 gap-4">
                <native:radio-group native:model="difficulty" label="Training pace">
                    <native:radio value="beginner" label="Gentle" />
                    <native:radio value="intermediate" label="Steady" />
                    <native:radio value="advanced" label="Challenging" />
                    <native:radio value="adaptive" label="Adaptive" />
                </native:radio-group>
            </native:column>
            </native:column>
        @elseif ($currentStep === 4)
            <native:column class="w-80 items-center">
                <x-native.onboarding-illustration
                    :ios="Ios::PersonCropCircle"
                    :android="AndroidOutlined::Person"
                    a11y-label="Local profile"
                    :motion-duration="$this->motionDuration()"
                    compact
                />
            </native:column>

            <native:column class="gap-2">
                <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">
                    What should we call you?
                </native:text>
                <native:text class="w-72 text-[15] leading-relaxed text-theme-secondary-text">
                    Optional. Your name stays on this device.
                </native:text>
            </native:column>

            <native:column class="w-full">
                <x-native.onboarding-display-name-input
                    :display-name="$displayName"
                    :valid="$this->isDisplayNameValid()"
                />
            </native:column>
        @elseif ($currentStep === 5)
            <native:column class="gap-2">
                <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">
                    Make it feel right.
                </native:text>
                <native:text class="w-72 text-[15] leading-relaxed text-theme-secondary-text">
                    Set your appearance and feedback preferences.
                </native:text>
            </native:column>

            <native:column class="w-full gap-4">
                <native:radio-group native:model="themePreference" label="Appearance">
                    <native:radio value="system" label="Use device setting" />
                    <native:radio value="light" label="Light" />
                    <native:radio value="dark" label="Dark" />
                </native:radio-group>
            </native:column>

            <native:column class="w-full gap-4 rounded-2xl bg-theme-secondary-surface">
                <native:toggle native:model="soundEnabled" label="Sound" />
                <native:divider />
                <native:toggle native:model="hapticsEnabled" label="Haptics" />
                <native:divider />
                <native:toggle
                    native:model="reducedMotion"
                    label="Reduce motion"
                    a11y-hint="Reduces non-essential movement throughout Ennoble"
                />
            </native:column>
        @else
            <native:column class="w-80 items-center gap-7">
                <x-native.onboarding-illustration
                    :ios="Ios::CheckmarkSeal"
                    :android="AndroidOutlined::Verified"
                    a11y-label="Training setup complete"
                    :animated="true"
                    :motion-duration="$this->motionDuration(MotionToken::Success)"
                />

                <native:column class="items-center gap-3">
                    <native:text class="text-[28] font-bold tracking-tight leading-tight text-center text-theme-primary-text">
                        Ready for day one.
                    </native:text>
                    <native:text class="w-72 text-[15] leading-relaxed text-center text-theme-secondary-text">
                        Your private training space is ready.
                    </native:text>
                </native:column>
            </native:column>

            @php
                $paceLabel = match ($difficulty) {
                    'beginner' => 'Gentle',
                    'intermediate' => 'Steady',
                    'advanced' => 'Challenging',
                    'adaptive' => 'Adaptive',
                    default => 'Not selected',
                };
            @endphp

            <native:column class="w-80 items-center">
            <native:column class="w-72 gap-1 rounded-2xl bg-theme-surface shadow-sm px-4 py-2">
                <x-native.onboarding-summary-row label="Focus" :value="$this->trainingGoalLabel()" />
                <native:divider />
                <x-native.onboarding-summary-row label="Pace" :value="$paceLabel" />
                <native:divider />
                <x-native.onboarding-summary-row label="Appearance" :value="$this->themeLabel()" />
            </native:column>
            </native:column>
        @endif
    </native:column>
    </native:row>
    </native:scroll-view>

    {{-- Fixed footer — one full-width primary action, a quiet Back beneath it.
         w-full on the buttons is the EDGE stretch mechanism (overrides items-center),
         so both actions fill the same width and align to the content above. --}}
    <native:row class="w-full justify-center bg-theme-background px-5 pt-3 pb-8">
        <native:column ref="onboarding-actions" class="w-80 gap-2">
            @if ($errorMessage)
                <native:column class="w-full rounded-2xl bg-theme-secondary-surface p-4">
                    <native:text class="text-[15] font-semibold text-theme-danger">
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
    </native:row>

</native:column>
