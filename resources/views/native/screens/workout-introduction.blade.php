@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$screenState" :scroll="true">
    @if ($screenState === 'error')
        <x-native.error-state title="Workout unavailable" :description="$errorMessage">
            <x-slot:retry>
                <native:button label="Retry workout" size="lg" variant="primary" @press="retry" />
            </x-slot:retry>
        </x-native.error-state>
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

    <native:column class="w-full gap-4 rounded-3xl bg-theme-surface p-5">
        <native:row class="w-full flex-wrap items-center gap-4">
            <native:row class="items-center gap-2">
                <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="20" />
                <native:text class="text-base font-semibold text-theme-on-surface">{{ $duration }}</native:text>
            </native:row>
            <native:row class="items-center gap-2">
                <x-native.icon :ios="Ios::Gauge" :android="AndroidOutlined::Speed" :size="20" />
                <native:text class="text-base font-semibold text-theme-on-surface">{{ $difficulty }}</native:text>
            </native:row>
        </native:row>

        <native:column class="w-full gap-3">
            <native:text class="text-xs font-semibold text-theme-primary">INCLUDED GAMES</native:text>
            @foreach ($games as $index => $game)
                <native:row class="w-full items-center gap-3" :native:key="$game['name']">
                    <native:column class="items-center justify-center rounded-full bg-theme-surface-variant p-3">
                        <native:text class="text-sm font-bold text-theme-primary">{{ $index + 1 }}</native:text>
                    </native:column>
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-base font-semibold text-theme-on-surface">{{ $game['name'] }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">{{ $game['duration'] }}</native:text>
                    </native:column>
                </native:row>
            @endforeach
        </native:column>

        <native:column class="w-full gap-2">
            <native:text class="text-xs font-semibold text-theme-primary">SKILLS INCLUDED</native:text>
            <native:text class="text-sm leading-relaxed text-theme-on-surface">{{ implode(' · ', $skills) }}</native:text>
        </native:column>
    </native:column>

    <native:column class="w-full gap-2 rounded-2xl bg-theme-surface-variant p-4">
        <native:text class="text-xs font-semibold text-theme-primary">A NOTE BEFORE YOU BEGIN</native:text>
        <native:text class="text-base leading-relaxed text-theme-on-surface">{{ $motivation }}</native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
            Signal Shift and Clear Thought remain honest placeholders in this prompt. No gameplay data is invented.
        </native:text>
    </native:column>

    <x-native.workout-footer>
        <x-slot:primary>
            <native:button
                :label="$actionLabel"
                size="lg"
                variant="primary"
                a11y-hint="Starts or resumes today’s local workout framework"
                @press="beginWorkout"
            />
        </x-slot:primary>
        <x-slot:secondary>
            <native:button label="Back" size="lg" variant="secondary" @press="goBack" />
        </x-slot:secondary>
    </x-native.workout-footer>
    @endif
</x-native.screen-container>
