@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'game',
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-5 rounded-3xl bg-theme-surface p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $game['title'] }} game"
>
    <native:row class="w-full items-center gap-4">
        <x-native.game-illustration :slug="$game['slug']" :motion-duration="$motionDuration" />
        <native:column class="flex-1 gap-2">
            <native:row class="w-full flex-wrap items-center gap-2">
                <x-native.game-badge :label="$game['category']" :motion-duration="$motionDuration" />
                <native:text class="text-xs font-semibold text-theme-primary">{{ $game['duration'] }}</native:text>
            </native:row>
            <native:text class="text-2xl font-bold leading-tight text-theme-on-surface">{{ $game['title'] }}</native:text>
        </native:column>
    </native:row>

    <native:text class="text-base leading-relaxed text-theme-on-surface-variant">{{ $game['description'] }}</native:text>

    <native:column class="w-full gap-1">
        <native:text class="text-xs font-semibold text-theme-primary">TRAINS</native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-surface">{{ implode(' · ', $game['skills']) }}</native:text>
    </native:column>

    <native:row class="w-full gap-3">
        <x-native.game-stat
            :ios="Ios::Trophy"
            :android="AndroidOutlined::EmojiEvents"
            label="Best score"
            :value="$game['best_score'] ?? 'No best yet'"
        />
        <x-native.game-stat
            :ios="Ios::PlayCircle"
            :android="AndroidOutlined::PlayCircle"
            label="Times played"
            :value="$game['session_count']"
        />
    </native:row>

    <native:row class="w-full gap-3">
        <x-native.game-stat
            :ios="Ios::CheckmarkSeal"
            :android="AndroidOutlined::CheckCircle"
            label="Completed"
            :value="$game['completion_count']"
        />
        <x-native.game-stat
            :ios="Ios::ClockArrowCirclepath"
            :android="AndroidOutlined::History"
            label="Last played"
            :value="$game['last_played']"
        />
    </native:row>

    <native:row class="w-full gap-3">
        <x-native.game-stat
            :ios="Ios::Gauge"
            :android="AndroidOutlined::Speed"
            label="Difficulty"
            :value="$game['difficulty'].' · '.$game['level']"
        />
    </native:row>

    <native:column class="w-full gap-2">
        <native:row class="w-full items-center justify-between">
            <native:text class="text-xs font-semibold text-theme-on-surface-variant">COMPLETION RATE</native:text>
            <native:text class="text-xs font-semibold text-theme-primary">
                {{ $game['completion_rate'] === null ? 'No data yet' : $game['completion_rate'].'%' }}
            </native:text>
        </native:row>
        <native:progress-bar
            :value="($game['completion_rate'] ?? 0) / 100"
            a11y-label="{{ $game['completion_rate'] === null ? 'No completion data yet' : $game['completion_rate'].' percent completion rate' }}"
        />
    </native:column>

    @if (! $game['has_history'])
        <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
            No history yet. Your first completed session will add a personal best and completion evidence here.
        </native:text>
    @endif

    <native:button
        label="Play"
        size="lg"
        variant="primary"
        a11y-hint="Opens the future {{ $game['title'] }} game flow preview"
        @press="openGame('{{ $game['slug'] }}')"
    />
</native:column>
