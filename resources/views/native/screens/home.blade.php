@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.loading-overlay label="Loading your home screen" />
    @elseif ($screenState === 'error')
        <x-native.error-state
            :description="$screenError"
            retry-label="Retry"
            retry-method="retryHome"
        />
    @else
    <x-native.dashboard-greeting
        :date="$todayLabel"
        :greeting="$greeting"
        :display-name="$displayName"
        :initial="$avatarInitial"
        :message="$greetingMessage"
        :motion-duration="$motionDuration"
    />

    {{-- Quick play --}}
    <x-native.dashboard-section-header title="Play" />

    @foreach ($games as $game)
        <x-native.home-play-card
            :slug="$game['slug']"
            :title="$game['title']"
            :subtitle="$game['subtitle']"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
            :motion-duration="$motionDuration"
        />
    @endforeach

    {{-- At a glance --}}
    <native:column class="w-full items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 gap-3">
        <native:row class="gap-3">
            <x-native.game-stat
                :ios="Ios::Flame"
                :android="AndroidOutlined::LocalFireDepartment"
                label="Day streak"
                :value="(string) $currentStreak"
            />
            <x-native.game-stat
                :ios="Ios::Crown"
                :android="AndroidOutlined::WorkspacePremium"
                label="Best score"
                :value="$bestLabel"
            />
        </native:row>
    </native:column>
    </native:column>

    {{-- Latest badge --}}
    <x-native.dashboard-section-header title="Latest badge" />

    <native:pressable
        class="w-full"
        :press-scale="$pressScale"
        :press-opacity="$pressOpacity"
        a11y-label="View all achievements"
        a11y-hint="Opens the Achievements screen"
        @press="openAchievements"
    >
        <x-native.dashboard-achievement-card
            :title="$achievementTitle"
            :description="$achievementDescription"
            :motion-duration="$motionDuration"
        />
    </native:pressable>
    @endif
</native:column>
</native:scroll-view>
</native:column>
