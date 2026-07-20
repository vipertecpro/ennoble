@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.ui.loading-overlay label="Loading your home screen" />
    @elseif ($screenState === 'error')
        <x-native.ui.error-state
            :description="$screenError"
            retry-label="Retry"
            retry-method="retryHome"
        />
    @else
    {{-- Header — plain text, merged with the page (no card) --}}
    <x-native.dashboard.greeting
        :date="$todayLabel"
        :greeting="$greeting"
        :display-name="$displayName"
        :message="$greetingMessage"
        :motion-duration="$motionDuration"
    />

    {{-- Recently played game (the full catalogue lives on the Games tab) --}}
    @if ($recentGame !== null)
        <x-native.dashboard.section-header :title="$playSectionTitle" />

        <x-native.dashboard.play-card
            :slug="$recentGame['slug']"
            :title="$recentGame['title']"
            :subtitle="$recentGame['subtitle']"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
            :motion-duration="$motionDuration"
        />
    @endif

    {{-- At a glance --}}
    <native:column class="w-full items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 gap-3">
        <native:row class="gap-3">
            {{-- Day streak — centered game-HUD stat --}}
            <native:column class="flex-1 items-center gap-1 rounded-2xl bg-theme-secondary-surface py-4 px-3">
                <native:column class="w-14 h-14">
                    <native:lottie-player source="flame" loop class="flex-1 w-full" alt="Streak flame" />
                </native:column>
                <native:text class="text-[30] font-bold leading-tight text-theme-primary-text">{{ $currentStreak }}</native:text>
                <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">Day streak</native:text>
            </native:column>

            {{-- Games played — centered game-HUD stat --}}
            <native:column class="flex-1 items-center gap-1 rounded-2xl bg-theme-secondary-surface py-4 px-3">
                <native:column class="w-14 h-14">
                    <native:lottie-player source="gaming" loop class="flex-1 w-full" alt="Games played" />
                </native:column>
                <native:text class="text-[30] font-bold leading-tight text-theme-primary-text">{{ $gamesPlayed }}</native:text>
                <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">Games played</native:text>
            </native:column>
        </native:row>
    </native:column>
    </native:column>

    {{-- Latest badge (the card self-labels "LATEST UNLOCK") --}}
    <native:pressable
        class="w-full"
        :press-scale="$pressScale"
        :press-opacity="$pressOpacity"
        a11y-label="View all achievements"
        a11y-hint="Opens the Achievements screen"
        @press="openAchievements"
    >
        <x-native.dashboard.achievement-card
            :title="$achievementTitle"
            :description="$achievementDescription"
            :motion-duration="$motionDuration"
        />
    </native:pressable>
    @endif
</native:column>
</native:scroll-view>
</native:column>
