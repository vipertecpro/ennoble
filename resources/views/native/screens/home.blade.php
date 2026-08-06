@use('App\Icons\Android')
@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')

<native:column class="h-full w-full {{ Gradients::screen() }}">
<native:scroll-view class="h-full flex-1" :shows-indicators="false">
<native:column class="w-full px-4 mt-2 mb-12 gap-5">
    @if ($screenState === 'loading')
        <x-native.ui.loading-overlay label="Loading your home screen" />
    @elseif ($screenState === 'error')
        <x-native.ui.error-state
            :description="$screenError"
            retry-label="Retry"
            retry-method="retryHome"
        />
    @else
    <x-native.ui.app-header
        :eyebrow="$todayLabel"
        :title="$greeting.', '.$displayName"
        :streak="$currentStreak"
        :level="$level"
    />

    {{-- Hero: continue the recently-played game --}}
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

    {{-- Compact 3-up stat strip --}}
    <native:row class="w-full gap-2.5" :animate-duration="$motionDuration">
        <x-native.ui.stat-chip
            :value="$currentStreak"
            label="Streak"
            token="accent-amber"
            :ios="Ios::FlameFill"
            :android="Android::LocalFireDepartment"
        />
        <x-native.ui.stat-chip
            :value="$gamesPlayed"
            label="Games"
            token="accent"
            :ios="Ios::GamecontrollerFill"
            :android="Android::SportsEsports"
        />
        <x-native.ui.stat-chip
            :value="$accuracyLabel"
            label="Accuracy"
            token="accent-cyan"
            :ios="Ios::Target"
            :android="AndroidOutlined::GpsFixed"
        />
    </native:row>

    {{-- Level / XP --}}
    <native:column class="w-full rounded-2xl bg-theme-surface-elevated border {{ Gradients::hairline() }} shadow-md px-4 py-4 gap-2.5">
        <native:row class="w-full items-center">
            <native:text class="flex-1 text-[13.5] font-semibold text-theme-primary-text">Level {{ $level }} · {{ $levelTitle }}</native:text>
            <native:text font="numeric" class="text-[12.5] text-theme-secondary-text">{{ $xpLabel }}</native:text>
        </native:row>
        <x-native.ui.progress :value="$levelProgress" token="accent" />
    </native:column>

    {{-- Your games — horizontal carousel --}}
    @if (count($games) > 0)
        <x-native.dashboard.section-header title="Your games" />
        <native:scroll-view horizontal :shows-indicators="false" class="w-full">
            <native:row class="items-stretch gap-2.5 pr-4">
                @foreach ($games as $game)
                    <x-native.games.shared.game-card :game="$game" />
                @endforeach
            </native:row>
        </native:scroll-view>
    @endif

    {{-- Latest badge --}}
    <native:column class="w-full rounded-2xl bg-theme-surface-elevated border {{ Gradients::hairline() }} overflow-hidden">
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
