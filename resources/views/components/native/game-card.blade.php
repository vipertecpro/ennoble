@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'game',
    'motionDuration' => 0,
])

<native:column
    class="w-full items-center rounded-2xl bg-theme-surface shadow-sm py-5"
    :animate-duration="$motionDuration"
    a11y-label="{{ $game['title'] }} game"
>
<native:column class="w-full px-4 gap-5">
    <native:row class="items-center gap-4">
        <x-native.game-illustration :slug="$game['slug']" :motion-duration="$motionDuration" />
        <native:column class="flex-1 gap-2">
            <native:row class="flex-wrap items-center gap-2">
                <x-native.game-badge :label="$game['category']" :motion-duration="$motionDuration" />
                <native:text class="text-[13] font-semibold text-theme-muted-text">{{ $game['duration'] }}</native:text>
            </native:row>
            <native:text class="text-[22] font-semibold tracking-tight leading-tight text-theme-primary-text">{{ $game['title'] }}</native:text>
        </native:column>
    </native:row>

    <native:text class="text-[17] leading-relaxed text-theme-secondary-text">{{ $game['description'] }}</native:text>

    <native:column class="gap-1">
        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">TRAINS</native:text>
        <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ implode(' · ', $game['skills']) }}</native:text>
    </native:column>

    <native:row class="gap-3">
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

    <native:row class="gap-3">
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

    <native:row class="gap-3">
        <x-native.game-stat
            :ios="Ios::Gauge"
            :android="AndroidOutlined::Speed"
            label="Difficulty"
            :value="$game['difficulty'].' · '.$game['level']"
        />
    </native:row>

    <native:column class="gap-2">
        <native:row class="items-center justify-between">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">COMPLETION RATE</native:text>
            <native:text class="text-[13] font-semibold text-theme-accent">
                {{ $game['completion_rate'] === null ? 'No data yet' : $game['completion_rate'].'%' }}
            </native:text>
        </native:row>
        <native:progress-bar
            :value="($game['completion_rate'] ?? 0) / 100"
            a11y-label="{{ $game['completion_rate'] === null ? 'No completion data yet' : $game['completion_rate'].' percent completion rate' }}"
        />
    </native:column>

    @if (! $game['has_history'])
        <native:text class="text-[15] leading-relaxed text-theme-secondary-text">
            No history yet. Your first completed session will add a personal best and completion evidence here.
        </native:text>
    @endif

    <native:button
        label="Play"
        class="w-32"
        size="md"
        variant="primary"
        a11y-hint="Opens the future {{ $game['title'] }} game flow preview"
        @press="openGame('{{ $game['slug'] }}')"
    />
</native:column>
</native:column>
