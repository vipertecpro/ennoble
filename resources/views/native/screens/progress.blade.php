<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.loading-overlay label="Loading your progress" />
    @elseif ($screenState === 'error')
        <x-native.error-state
            :description="$screenError"
            retry-label="Retry progress"
            retry-method="retryProgressScreen"
        />
    @else
    <native:column class="gap-2">
        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">QUIET EVIDENCE</native:text>
        <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">Progress you can trust.</native:text>
        <native:text class="text-[17] leading-relaxed text-theme-secondary-text">
            Everything here comes from your own completed training, measured on this device and never estimated.
        </native:text>
    </native:column>

    @if ($isStatisticsLoading)
        <x-native.dashboard-loading-card label="Loading training rhythm" />
    @elseif ($statisticsError)
        <x-native.error-state
            :description="$statisticsError"
            retry-label="Retry summary"
            retry-method="retryStatistics"
        />
    @else
        <x-native.progress-rhythm-card
            :current="$currentStreak"
            :longest="$longestStreak"
            :weekly-days="$weeklyDays"
            :weekly-completed="$weeklyCompleted"
            :motion-duration="$motionDuration"
        />
    @endif

    <x-native.dashboard-section-header title="Skill profile" />

    @if ($isSkillsLoading)
        <x-native.dashboard-loading-card label="Loading skill profile" />
    @elseif ($skillsError)
        <x-native.error-state
            :description="$skillsError"
            retry-label="Retry skills"
            retry-method="retrySkills"
        />
    @else
        <x-native.progress-skills-card
            :skills="$skills"
            :motion-duration="$motionDuration"
        />
    @endif

    <x-native.dashboard-section-header title="Training summary" />

    @if ($isStatisticsLoading)
        <x-native.dashboard-loading-card label="Loading training summary" />
    @elseif ($statisticsError)
        <x-native.error-state
            :description="$statisticsError"
            retry-label="Retry summary"
            retry-method="retryStatistics"
        />
    @else
        <x-native.progress-training-card
            :has-evidence="$hasTrainingEvidence"
            :workouts="$workoutsLabel"
            :sessions="$sessionsLabel"
            :training-time="$trainingTimeLabel"
            :accuracy="$accuracyLabel"
            :response="$responseLabel"
            :combo="$comboLabel"
            :motion-duration="$motionDuration"
        />
    @endif

    @if (! $isStatisticsLoading && ! $statisticsError && count($gameBests) > 0)
        <x-native.dashboard-section-header title="Personal bests" />

        @foreach ($gameBests as $gameBest)
            <x-native.progress-game-card
                :name="$gameBest['name']"
                :best="$gameBest['best']"
                :sessions="$gameBest['sessions']"
                :accuracy="$gameBest['accuracy']"
                :motion-duration="$motionDuration"
            />
        @endforeach
    @endif

    @if ($isAchievementsLoading)
        <x-native.dashboard-section-header title="Achievements" />
        <x-native.dashboard-loading-card label="Loading achievements" />
    @elseif ($achievementsError)
        <x-native.dashboard-section-header title="Achievements" />
        <x-native.error-state
            :description="$achievementsError"
            retry-label="Retry achievements"
            retry-method="retryAchievements"
        />
    @elseif ($achievementsTotal > 0)
        <x-native.dashboard-section-header
            title="Achievements"
            eyebrow="{{ $achievementsUnlocked }} OF {{ $achievementsTotal }} UNLOCKED"
        />

        <native:column class="rounded-2xl bg-theme-surface shadow-sm">
            @foreach ($achievements as $achievement)
                <x-native.progress-achievement-row
                    :name="$achievement['name']"
                    :description="$achievement['description']"
                    :detail="$achievement['detail']"
                    :unlocked="$achievement['unlocked']"
                />

                @unless ($loop->last)
                    <native:divider />
                @endunless
            @endforeach
        </native:column>
    @endif
    @endif
</native:column>
</native:scroll-view>
</native:column>
