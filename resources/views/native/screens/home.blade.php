@use('App\Icons\Android')
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

    {{-- At a glance — punchy gradient stat badges (counts: streak + games).
         Gauge DIALS live on the %-based stat screens where a bounded arc reads
         well; raw counts get these bold badges instead. --}}
    <native:row class="w-full gap-3" :animate-duration="$motionDuration">
        {{-- Day streak — fire gradient --}}
        <native:column class="flex-1 items-center gap-2 rounded-3xl bg-linear-to-b from-orange-500/40 via-red-600/12 to-transparent border border-orange-500/50 shadow-lg py-5 px-3">
            <native:lottie-player source="flame" loop class="w-12 h-12" alt="Streak flame" />
            <native:text class="text-[34] font-bold leading-none text-theme-primary-text">{{ $currentStreak }}</native:text>
            <native:text class="text-[11] font-semibold uppercase tracking-widest text-orange-400">Day streak</native:text>
        </native:column>

        {{-- Games played — lime → cyan gradient --}}
        <native:column class="flex-1 items-center gap-2 rounded-3xl bg-linear-to-b from-lime-400/40 via-cyan-500/12 to-transparent border border-lime-400/50 shadow-lg py-5 px-3">
            <native:lottie-player source="gaming" loop class="w-12 h-12" alt="Games played" />
            <native:text class="text-[34] font-bold leading-none text-theme-primary-text">{{ $gamesPlayed }}</native:text>
            <native:text class="text-[11] font-semibold uppercase tracking-widest text-lime-400">Games played</native:text>
        </native:column>
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
