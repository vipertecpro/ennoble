@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'game',
    'motionDuration' => 0,
])

<native:column
    class="w-80 items-center rounded-2xl bg-theme-surface-elevated shadow-sm py-5"
    :animate-duration="$motionDuration"
    a11y-label="Featured game, {{ $game['title'] }}"
>
<native:column class="w-72 gap-5">
    <x-native.game-illustration
        :slug="$game['slug']"
        :hero="true"
        :motion-duration="$motionDuration"
    />

    <native:column class="gap-3">
        <native:row class="flex-wrap items-center gap-2">
            <x-native.game-badge
                label="FEATURED"
                :motion-duration="$motionDuration"
            />
            <native:text class="text-[13] font-semibold text-theme-muted-text">{{ $game['category'] }}</native:text>
        </native:row>
        <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">{{ $game['title'] }}</native:text>
        <native:text class="text-[17] leading-relaxed text-theme-secondary-text">{{ $game['description'] }}</native:text>
    </native:column>

    <native:row class="flex-wrap gap-3">
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="18" />
            <native:text class="text-[15] font-semibold text-theme-secondary-text">{{ $game['duration'] }}</native:text>
        </native:row>
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Gauge" :android="AndroidOutlined::Speed" :size="18" />
            <native:text class="text-[15] font-semibold text-theme-secondary-text">
                {{ $game['difficulty'] }} · {{ $game['level'] }}
            </native:text>
        </native:row>
    </native:row>

    <native:column class="gap-1">
        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SKILL FOCUS</native:text>
        <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ implode(' · ', $game['skills']) }}</native:text>
    </native:column>

    <native:row class="gap-3">
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary-surface p-3">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">PERSONAL BEST</native:text>
            <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">
                {{ $game['best_score'] ?? 'No best yet' }}
            </native:text>
        </native:column>
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary-surface p-3">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">LAST PLAYED</native:text>
            <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $game['last_played'] }}</native:text>
        </native:column>
    </native:row>

    <native:button
        :label="$game['hero_action']"
        class="w-40"
        size="md"
        variant="primary"
        a11y-hint="Opens the future {{ $game['title'] }} training flow preview"
        @press="openGame('{{ $game['slug'] }}')"
    />
</native:column>
</native:column>
