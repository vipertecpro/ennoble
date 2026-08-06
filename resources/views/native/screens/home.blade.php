@use('App\Icons\Android')
@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background safe-area-top">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-4 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.ui.loading-overlay label="Loading your home screen" />
    @elseif ($screenState === 'error')
        <x-native.ui.error-state
            :description="$screenError"
            retry-label="Retry"
            retry-method="retryHome"
        />
    @else
    {{-- Consistent gamified header: greeting + streak / level pills + settings --}}
    <x-native.ui.app-header
        :eyebrow="$todayLabel"
        :title="$greeting.', '.$displayName"
        :streak="$currentStreak"
        :level="$level"
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

    {{-- At a glance — glowing stat tiles (streak + games) --}}
    <native:row class="w-full gap-3" :animate-duration="$motionDuration">
        <native:column class="flex-1 items-center gap-2 rounded-3xl bg-linear-to-b from-orange-500/40 via-red-600/12 to-transparent border border-orange-500/50 shadow-lg py-5 px-3">
            <native:lottie-player source="flame" loop class="w-12 h-12" alt="Streak flame" />
            <native:text font="numeric" class="text-[34] leading-none text-theme-primary-text">{{ $currentStreak }}</native:text>
            <native:text class="text-[11] font-semibold uppercase tracking-widest text-orange-400">Day streak</native:text>
        </native:column>

        <native:column class="flex-1 items-center gap-2 rounded-3xl bg-linear-to-b from-lime-400/40 via-cyan-500/12 to-transparent border border-lime-400/50 shadow-lg py-5 px-3">
            <native:lottie-player source="gaming" loop class="w-12 h-12" alt="Games played" />
            <native:text font="numeric" class="text-[34] leading-none text-theme-primary-text">{{ $gamesPlayed }}</native:text>
            <native:text class="text-[11] font-semibold uppercase tracking-widest text-lime-400">Games played</native:text>
        </native:column>
    </native:row>

    {{-- Level / XP — the header pill's story, expanded --}}
    <native:column class="w-full rounded-2xl bg-theme-surface-elevated border border-theme-border shadow-md px-4 py-4 gap-2.5">
        <native:row class="w-full items-center">
            <native:text class="flex-1 text-[13.5] font-semibold text-theme-primary-text">Level {{ $level }} · {{ $levelTitle }}</native:text>
            <native:text font="numeric" class="text-[12.5] text-theme-secondary-text">{{ $xpLabel }}</native:text>
        </native:row>
        <x-native.ui.progress :value="$levelProgress" token="accent" />
    </native:column>

    {{-- Latest badge — a tappable list row into the Badges screen --}}
    <native:column class="w-full rounded-2xl bg-theme-surface-elevated border border-theme-border overflow-hidden">
        <x-native.ui.list-row
            :ios="Ios::Rosette"
            :android="AndroidOutlined::MilitaryTech"
            iconSolid="bg-linear-to-br from-amber-400 to-orange-500"
            :title="$achievementTitle ?? 'No badges yet'"
            :subtitle="$achievementTitle ? 'Latest unlock' : 'Play to earn your first'"
            chevron
            method="openAchievements"
        />
    </native:column>
    @endif
</native:column>
</native:scroll-view>
</native:column>
