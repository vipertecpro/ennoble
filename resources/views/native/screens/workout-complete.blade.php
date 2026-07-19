@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mb-12 mt-7 gap-6">
    @if ($screenState === 'error')
        <x-native.error-state
            title="Summary unavailable"
            :description="$errorMessage"
            retry-label="Return home"
            retry-method="continueHome"
        />
    @elseif ($phase === 'celebration')
        <x-native.workout-progress
            current-game="Daily rhythm complete"
            :games-remaining="0"
            :progress="1"
            time-estimate="Finished"
            :steps="$journeySteps"
        />

        <x-native.completion-card
            :duration="$duration"
            :games-completed="$gamesCompleted"
            :coaching="$coaching"
            :best-moment-title="$bestMomentTitle"
            :best-moment-detail="$bestMomentDetail"
            :motion-duration="$motionDuration"
        />

        <native:column class="w-full items-center">
            <native:button class="w-56" label="See today’s progress" size="lg" variant="primary" @press="showTodayProgress" />
        </native:column>
    @else
        <x-native.workout-header
            eyebrow="Today’s progress"
            title="The work that moved."
            subtitle="Only meaningful changes from this workout."
            :motion-duration="$motionDuration"
        />

        <native:column class="gap-5 rounded-2xl bg-theme-surface-elevated shadow-sm p-5">
            <native:column class="gap-3">
                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SKILL IMPROVEMENTS</native:text>
                @forelse ($skillImprovements as $improvement)
                    <native:row class="items-center justify-between gap-4" :native:key="$improvement['skill']">
                        <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $improvement['skill'] }}</native:text>
                        <native:text class="text-[17] font-semibold text-theme-success">{{ $improvement['change'] }}</native:text>
                    </native:row>
                @empty
                    <native:text class="text-[15] leading-relaxed text-theme-secondary-text">
                        No new skill change was recorded for this workout.
                    </native:text>
                @endforelse
            </native:column>

            <native:divider />

            <native:column class="gap-2">
                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">BEST MOMENT</native:text>
                <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $bestMomentTitle }}</native:text>
                <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ $bestMomentDetail }}</native:text>
            </native:column>

            <native:divider />

            <native:row class="items-center gap-4">
                <native:column class="h-12 w-12 items-center justify-center rounded-full bg-theme-primary-surface">
                    <x-native.icon :ios="Ios::Flame" :android="AndroidOutlined::LocalFireDepartment" :size="24" />
                </native:column>
                <native:column class="flex-1 gap-1">
                    <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">STREAK</native:text>
                    <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $streakMessage }}</native:text>
                </native:column>
            </native:row>

            @if ($achievementTitle)
                <native:divider />

                <native:row class="items-center gap-4">
                    <native:column class="h-12 w-12 items-center justify-center rounded-full bg-theme-primary-surface">
                        <x-native.icon :ios="Ios::Trophy" :android="AndroidOutlined::EmojiEvents" :size="24" />
                    </native:column>
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">ACHIEVEMENT UNLOCKED</native:text>
                        <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $achievementTitle }}</native:text>
                        <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ $achievementDescription }}</native:text>
                    </native:column>
                </native:row>
            @endif
        </native:column>

        <native:column class="w-full items-center">
            <native:button class="w-56" label="Return home" size="lg" variant="primary" @press="continueHome" />
        </native:column>
    @endif
</native:column>
</native:scroll-view>
</native:column>
