@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mb-12 mt-7 gap-6">
    @if ($screenState === 'error')
        <x-native.error-state
            title="Workout unavailable"
            :description="$errorMessage"
            retry-label="Retry workout"
            retry-method="retry"
        />
    @else
        <x-native.workout-header
            eyebrow="Today’s workout"
            title="Ready your mind."
            subtitle="One calm sequence. Two focused shifts. A clear finish."
            :motion-duration="$motionDuration"
        />

        <x-native.workout-progress
            :current-game="$actionLabel === 'Resume Workout' ? 'Continue your rhythm' : 'Your rhythm is ready'"
            :games-remaining="count($games)"
            :progress="0"
            :time-estimate="$duration"
            :steps="$journeySteps"
        />

        <native:column class="gap-5 rounded-2xl bg-theme-surface-elevated shadow-sm p-5">
            <native:row class="items-center justify-between gap-4">
                <native:row class="items-center gap-2">
                    <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="18" />
                    <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $duration }}</native:text>
                </native:row>
                <native:row class="items-center gap-2">
                    <x-native.icon :ios="Ios::Gauge" :android="AndroidOutlined::Speed" :size="18" />
                    <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $difficulty }}</native:text>
                </native:row>
            </native:row>

            <native:column class="gap-4">
                @foreach ($games as $index => $game)
                    <native:row class="items-center gap-4" :native:key="$game['name']">
                        <native:column class="h-11 w-11 items-center justify-center rounded-full bg-theme-primary-surface">
                            <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $index + 1 }}</native:text>
                        </native:column>
                        <native:column class="flex-1 gap-1">
                            <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $game['name'] }}</native:text>
                            <native:text class="text-[15] text-theme-secondary-text">
                                {{ $game['name'] === 'Clear Thought' ? 'Guided practice preview · '.$game['duration'] : $game['duration'] }}
                            </native:text>
                        </native:column>
                    </native:row>
                @endforeach
            </native:column>

            <native:divider />

            <native:column class="gap-2">
                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SKILLS TRAINED</native:text>
                <native:text class="text-[15] leading-relaxed text-theme-primary-text">{{ implode(' · ', $skills) }}</native:text>
            </native:column>
        </native:column>

        <native:column class="gap-2 px-2">
            <native:text class="text-[17] font-semibold leading-tight text-theme-primary-text">Start settled.</native:text>
            <native:text class="text-[17] leading-relaxed text-theme-secondary-text">{{ $motivation }}</native:text>
        </native:column>

        <native:column class="w-80 items-center gap-3">
            <native:button
                class="w-56"
                :label="$actionLabel"
                size="lg"
                variant="primary"
                a11y-hint="Starts or resumes today’s local workout"
                @press="beginWorkout"
            />
            <native:button class="w-56" label="Not now" size="md" variant="ghost" @press="goBack" />
        </native:column>
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
