@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background safe-area-top">
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

    {{-- At a glance — vibrant gradient stat badges (first proof of the
         game-premium direction: gradient fills + glowing coloured borders) --}}
    <native:row class="w-full gap-3" :animate-duration="$motionDuration">
        {{-- Day streak — fire gradient --}}
        <x-native.ui.stat-badge
            :value="$currentStreak"
            label="Day streak"
            accent="orange-500"
            accentTo="red-600"
            labelColor="orange-400"
        >
            <x-slot:icon>
                <native:lottie-player source="flame" loop class="flex-1 w-full" alt="Streak flame" />
            </x-slot:icon>
        </x-native.ui.stat-badge>

        {{-- Games played — lime → cyan gradient --}}
        <x-native.ui.stat-badge
            :value="$gamesPlayed"
            label="Games played"
            accent="lime-400"
            accentTo="cyan-500"
            labelColor="lime-400"
        >
            <x-slot:icon>
                <native:lottie-player source="gaming" loop class="flex-1 w-full" alt="Games played" />
            </x-slot:icon>
        </x-native.ui.stat-badge>
    </native:row>

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
