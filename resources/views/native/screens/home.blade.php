@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$dashboardState" :scroll="true">
    <x-slot:loading>
        <x-native.loading-overlay label="Loading your Ennoble dashboard" />
    </x-slot:loading>

    <x-slot:error>
        <x-native.error-state :description="$dashboardError">
            <x-slot:retry>
                <native:button label="Retry dashboard" variant="secondary" @press="retryDashboard" />
            </x-slot:retry>
        </x-native.error-state>
    </x-slot:error>

    <x-native.dashboard-greeting
        :greeting="$greeting"
        :display-name="$displayName"
        :message="$greetingMessage"
        :motion-duration="$motionDuration"
    />

    <x-native.dashboard-section-header title="Today’s Workout" eyebrow="YOUR DAILY PRACTICE" />

    @if ($isWorkoutLoading)
        <x-native.dashboard-loading-card label="Preparing today’s workout" />
    @elseif ($workoutError)
        <x-native.error-state :description="$workoutError">
            <x-slot:retry>
                <native:button label="Retry workout" variant="secondary" @press="retryWorkout" />
            </x-slot:retry>
        </x-native.error-state>
    @else
        <x-native.dashboard-workout-card
            :title="$workoutTitle"
            :duration="$workoutDuration"
            :skills="$workoutSkills"
            :difficulty="$workoutDifficulty"
            :action="$workoutAction"
            :status="$workoutStatus"
            :completion-percentage="$workoutCompletionPercentage"
            :motion-duration="$motionDuration"
        />
    @endif

    <x-native.dashboard-section-header title="Current Streak" />

    @if ($isStatisticsLoading)
        <x-native.dashboard-loading-card label="Loading streak and personal bests" />
    @elseif ($statisticsError)
        <x-native.error-state :description="$statisticsError">
            <x-slot:retry>
                <native:button label="Retry statistics" variant="secondary" @press="retryStatistics" />
            </x-slot:retry>
        </x-native.error-state>
    @else
        <x-native.dashboard-streak-card
            :current="$currentStreak"
            :longest="$longestStreak"
            :motion-duration="$motionDuration"
        />
    @endif

    <x-native.dashboard-section-header title="Progress Snapshot" />

    @if ($isProgressLoading)
        <x-native.dashboard-loading-card label="Loading skill progress" />
    @elseif ($progressError)
        <x-native.error-state :description="$progressError">
            <x-slot:retry>
                <native:button label="Retry progress" variant="secondary" @press="retryProgress" />
            </x-slot:retry>
        </x-native.error-state>
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
        <x-native.error-state :description="$achievementError">
            <x-slot:retry>
                <native:button label="Retry achievement" variant="secondary" @press="retryAchievement" />
            </x-slot:retry>
        </x-native.error-state>
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

    <native:column class="w-full rounded-3xl bg-theme-surface">
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

    <x-slot:overlays>
        <x-native.dialog-host
            :dialog-visible="$dialogVisible"
            :bottom-sheet-visible="$bottomSheetVisible"
            sheet-a11y-label="Coming soon experience details"
        >
            <x-slot:sheet>
                <native:column class="w-full gap-4 p-5">
                    <native:text class="text-xs font-semibold text-theme-primary">COMING SOON</native:text>
                    <native:text class="text-2xl font-bold text-theme-on-surface">{{ $comingSoonTitle }}</native:text>
                    <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
                        {{ $comingSoonDescription }}
                    </native:text>
                    <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
                        This preview is informational only. It does not create a session or open gameplay.
                    </native:text>
                    <native:button label="Got it" variant="secondary" @press="dismissBottomSheet" />
                </native:column>
            </x-slot:sheet>
        </x-native.dialog-host>
    </x-slot:overlays>
</x-native.screen-container>
