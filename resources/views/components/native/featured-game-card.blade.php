@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'game',
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-5 rounded-3xl bg-theme-primary p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="Featured game, {{ $game['title'] }}"
>
    <x-native.game-illustration
        :slug="$game['slug']"
        :hero="true"
        :motion-duration="$motionDuration"
    />

    <native:column class="w-full gap-3">
        <native:row class="w-full flex-wrap items-center gap-2">
            <x-native.game-badge
                label="FEATURED"
                :motion-duration="$motionDuration"
            />
            <native:text class="text-xs font-semibold text-theme-on-primary">{{ $game['category'] }}</native:text>
        </native:row>
        <native:text class="text-3xl font-bold leading-tight text-theme-on-primary">{{ $game['title'] }}</native:text>
        <native:text class="text-base leading-relaxed text-theme-on-primary">{{ $game['description'] }}</native:text>
    </native:column>

    <native:row class="w-full flex-wrap gap-3">
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="18" />
            <native:text class="text-sm font-semibold text-theme-on-primary">{{ $game['duration'] }}</native:text>
        </native:row>
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Gauge" :android="AndroidOutlined::Speed" :size="18" />
            <native:text class="text-sm font-semibold text-theme-on-primary">
                {{ $game['difficulty'] }} · {{ $game['level'] }}
            </native:text>
        </native:row>
    </native:row>

    <native:column class="w-full gap-1">
        <native:text class="text-xs font-semibold text-theme-on-primary">SKILL FOCUS</native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-primary">{{ implode(' · ', $game['skills']) }}</native:text>
    </native:column>

    <native:row class="w-full gap-3">
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary p-3">
            <native:text class="text-xs font-semibold text-theme-on-secondary">PERSONAL BEST</native:text>
            <native:text class="text-xl font-bold text-theme-on-secondary">
                {{ $game['best_score'] ?? 'No best yet' }}
            </native:text>
        </native:column>
        <native:column class="flex-1 gap-1 rounded-2xl bg-theme-secondary p-3">
            <native:text class="text-xs font-semibold text-theme-on-secondary">LAST PLAYED</native:text>
            <native:text class="text-base font-semibold text-theme-on-secondary">{{ $game['last_played'] }}</native:text>
        </native:column>
    </native:row>

    <native:button
        :label="$game['hero_action']"
        size="lg"
        variant="secondary"
        a11y-hint="Opens the future {{ $game['title'] }} training flow preview"
        @press="openGame('{{ $game['slug'] }}')"
    />
</native:column>
