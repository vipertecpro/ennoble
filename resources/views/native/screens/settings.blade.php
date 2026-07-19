@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.loading-overlay label="Loading your preferences" />
    @elseif ($screenState === 'error')
        <x-native.error-state
            :description="$screenError"
            retry-label="Retry settings"
            retry-method="retrySettings"
        />
    @else
    <x-native.dashboard-section-header title="Appearance" />

    <native:column class="w-full items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 gap-4">
        <native:radio-group native:model="themePreference" label="Appearance">
            <native:radio value="system" label="Use device setting" />
            <native:radio value="light" label="Light" />
            <native:radio value="dark" label="Dark" />
        </native:radio-group>

        <native:text class="text-[13] leading-relaxed text-theme-muted-text">
            Applied immediately across every screen.
        </native:text>
    </native:column>
    </native:column>

    <x-native.dashboard-section-header title="Feedback &amp; motion" />

    <native:column class="w-full items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 gap-4">
        <native:toggle native:model="soundEnabled" label="Sound" />
        <native:divider />
        <native:toggle native:model="hapticsEnabled" label="Haptics" />
        <native:divider />
        <native:toggle
            native:model="reducedMotion"
            label="Reduce motion"
            a11y-hint="Reduces non-essential movement throughout Ennoble"
        />

        <native:text class="text-[13] leading-relaxed text-theme-muted-text">
            Reduce motion removes non-essential movement while keeping every gameplay cue readable.
        </native:text>
    </native:column>
    </native:column>

    <x-native.dashboard-section-header title="More" />

    <native:column class="rounded-2xl bg-theme-surface shadow-sm">
        <x-native.settings-link-row
            label="About Ennoble"
            description="What Ennoble is and how it protects your privacy."
            method="openAbout"
            :ios="Ios::Info"
            :android="AndroidOutlined::Info"
        />
    </native:column>

    <x-native.dashboard-section-header title="Data" />

    <native:column class="w-full rounded-2xl bg-theme-surface shadow-sm p-4 gap-3" :animate-duration="$motionDuration">
        @if ($resetArmed)
            <native:text class="text-[15] font-semibold text-theme-primary-text">Reset everything?</native:text>
            <native:text class="text-[13] leading-relaxed text-theme-secondary-text">
                This permanently clears your stats, streak, earned badges, and game history on this device. Your name and settings stay.
            </native:text>
            <native:row class="w-full gap-3">
                <native:button class="flex-1" label="Cancel" size="md" variant="secondary" @press="cancelReset" />
                <native:button class="flex-1" label="Reset everything" size="md" variant="primary" @press="resetProgress" />
            </native:row>
        @else
            <native:pressable
                @press="armReset"
                :press-scale="0.99"
                a11y-label="Reset stats and badges"
                a11y-hint="Clears all local progress after a confirmation"
            >
                <native:row class="w-full items-center gap-4">
                    <native:column class="items-center justify-center rounded-xl bg-theme-secondary-surface p-3">
                        <x-native.icon
                            :ios="Ios::ArrowCounterclockwise"
                            :android="AndroidOutlined::RestartAlt"
                            :size="24"
                        />
                    </native:column>
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-[15] font-semibold text-theme-danger">Reset stats &amp; badges</native:text>
                        <native:text class="text-[13] leading-relaxed text-theme-secondary-text">Clear all progress and start fresh.</native:text>
                    </native:column>
                </native:row>
            </native:pressable>
        @endif
    </native:column>

    <native:text class="text-[13] leading-relaxed text-center text-theme-muted-text">
        Every preference is stored only on this device.
    </native:text>
    @endif
</native:column>
</native:scroll-view>
</native:column>
