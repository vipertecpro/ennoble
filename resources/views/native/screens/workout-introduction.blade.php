@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
    @if ($screenState === 'error')
        <x-native.error-state
            title="Workout unavailable"
            :description="$errorMessage"
            retry-label="Retry workout"
            retry-method="retry"
        />
    @else
    <x-native.workout-header
        eyebrow="Daily Momentum"
        title="A focused sequence for today"
        subtitle="Two short games, one calm rhythm, and a clear finish."
        :motion-duration="$motionDuration"
    />

    <x-native.workout-progress
        :current-game="$games[0]['name'] ?? 'Ready to begin'"
        :games-remaining="count($games)"
        :progress="0"
        :time-estimate="$duration"
    />

    <native:column class="w-80 items-center rounded-3xl border border-theme-border bg-theme-surface-elevated py-5">
    <native:column class="w-72 gap-5">
        <native:row class="flex-wrap items-center gap-4">
            <native:row class="items-center gap-2">
                <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="20" />
                <native:text class="text-base font-semibold text-theme-primary-text">{{ $duration }}</native:text>
            </native:row>
            <native:row class="items-center gap-2">
                <x-native.icon :ios="Ios::Gauge" :android="AndroidOutlined::Speed" :size="20" />
                <native:text class="text-base font-semibold text-theme-primary-text">{{ $difficulty }}</native:text>
            </native:row>
        </native:row>

        <native:column class="gap-3">
            <native:text class="text-xs font-semibold text-theme-accent">INCLUDED GAMES</native:text>
            @foreach ($games as $index => $game)
                <native:row class="items-center gap-3" :native:key="$game['name']">
                    <native:column class="items-center justify-center rounded-2xl bg-theme-primary-surface p-3">
                        <native:text class="text-sm font-bold text-theme-accent">{{ $index + 1 }}</native:text>
                    </native:column>
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-base font-semibold text-theme-primary-text">{{ $game['name'] }}</native:text>
                        <native:text class="text-sm text-theme-secondary-text">{{ $game['duration'] }}</native:text>
                    </native:column>
                </native:row>
            @endforeach
        </native:column>

        <native:column class="gap-2">
            <native:text class="text-xs font-semibold text-theme-accent">SKILLS INCLUDED</native:text>
            <native:text class="text-sm leading-relaxed text-theme-primary-text">{{ implode(' · ', $skills) }}</native:text>
        </native:column>
    </native:column>
    </native:column>

    <native:column class="gap-2 rounded-2xl bg-theme-secondary-surface p-4">
        <native:text class="text-xs font-semibold text-theme-accent">A NOTE BEFORE YOU BEGIN</native:text>
        <native:text class="text-base leading-relaxed text-theme-primary-text">{{ $motivation }}</native:text>
        <native:text class="text-sm leading-relaxed text-theme-secondary-text">
            Signal Shift records real local gameplay evidence. Clear Thought remains an explicit framework-only step until its dedicated game is implemented.
        </native:text>
    </native:column>

    <native:column class="w-80 items-center gap-3">
        <native:button
            class="w-56"
            :label="$actionLabel"
            size="md"
            variant="primary"
            a11y-hint="Starts or resumes today’s local workout"
            @press="beginWorkout"
        />
        <native:button class="w-56" label="Back" size="md" variant="ghost" @press="goBack" />
    </native:column>
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
