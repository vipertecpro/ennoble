@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
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
        :greeting="$greeting"
        :display-name="$displayName"
        :message="$greetingMessage"
        :motion-duration="$motionDuration"
    />

    @if ($isWorkoutLoading)
        <x-native.dashboard-loading-card label="Preparing today’s workout" />
    @elseif ($workoutError)
        <x-native.error-state
            :description="$workoutError"
            retry-label="Retry workout"
            retry-method="retryWorkout"
        />
    @else
        <x-native.dashboard-workout-card
            :title="$workoutTitle"
            :duration="$workoutDuration"
            :difficulty="$workoutDifficulty"
            :action="$workoutAction"
            :status="$workoutStatus"
            :completion-percentage="$workoutCompletionPercentage"
            :motion-duration="$motionDuration"
        />
    @endif

    <x-native.dashboard-section-header title="Your rhythm" />

    @if ($isStatisticsLoading)
        <x-native.dashboard-loading-card label="Loading streak and personal bests" />
    @elseif ($statisticsError)
        <x-native.error-state
            :description="$statisticsError"
            retry-label="Retry statistics"
            retry-method="retryStatistics"
        />
    @else
        <x-native.dashboard-streak-card
            :current="$currentStreak"
            :longest="$longestStreak"
            :motion-duration="$motionDuration"
        />
    @endif

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

    <x-native.dashboard-section-header
        title="On the Horizon"
        eyebrow="COMING SOON"
    />

    <native:column class="rounded-3xl border border-theme-border bg-theme-surface-elevated">
        <x-native.dashboard-coming-soon-card
            experience="memory-path"
            title="Memory Path"
            description="Recall ordered visual journeys."
            :ios="Ios::Point3ConnectedTrianglepathDotted"
            :android="AndroidOutlined::Route"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
        />
        <native:divider />
        <x-native.dashboard-coming-soon-card
            experience="pattern-pulse"
            title="Pattern Pulse"
            description="Recognize shifting sequences."
            :ios="Ios::WaveformPathEcg"
            :android="AndroidOutlined::Pattern"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
        />
        <native:divider />
        <x-native.dashboard-coming-soon-card
            experience="word-forge"
            title="Word Forge"
            description="Shape language with precision."
            :ios="Ios::Textformat"
            :android="AndroidOutlined::TextFields"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
        />
        <native:divider />
        <x-native.dashboard-coming-soon-card
            experience="quick-read"
            title="Quick Read"
            description="Build efficient comprehension."
            :ios="Ios::Book"
            :android="AndroidOutlined::MenuBook"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
        />
    </native:column>
    @endif

    @if ($bottomSheetVisible)
        @include('native.partials.home-coming-soon-sheet')
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
