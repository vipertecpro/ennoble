@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($dashboardState === 'loading')
        <x-native.loading-overlay label="Loading your Ennoble dashboard" />
    @elseif ($dashboardState === 'error')
        <x-native.error-state
            :description="$dashboardError"
            retry-label="Retry dashboard"
            retry-method="retryDashboard"
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

    <x-native.dashboard-section-header title="Progress" />

    @if ($isProgressLoading)
        <x-native.dashboard-loading-card label="Loading skill progress" />
    @elseif ($progressError)
        <x-native.error-state
            :description="$progressError"
            retry-label="Retry progress"
            retry-method="retryProgress"
        />
    @else
        <x-native.dashboard-progress-card
            :skill-highlights="$skillHighlights"
            :weekly-completed="$weeklyCompleted"
            :weekly-completion-percentage="$weeklyCompletionPercentage"
            :personal-best-score="$personalBestScore"
            :personal-best-game="$personalBestGame"
            :has-workout-history="$hasWorkoutHistory"
            :motion-duration="$motionDuration"
        />
    @endif

    <x-native.dashboard-section-header title="Recent Achievement" />

    @if ($isAchievementLoading)
        <x-native.dashboard-loading-card label="Loading latest achievement" />
    @elseif ($achievementError)
        <x-native.error-state
            :description="$achievementError"
            retry-label="Retry achievement"
            retry-method="retryAchievement"
        />
    @else
        <x-native.dashboard-achievement-card
            :title="$achievementTitle"
            :description="$achievementDescription"
            :motion-duration="$motionDuration"
        />
    @endif

    @endif
</native:column>
</native:scroll-view>
</native:column>
