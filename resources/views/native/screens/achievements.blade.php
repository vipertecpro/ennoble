@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')

<native:column class="h-full w-full {{ Gradients::screen() }}">
<native:scroll-view class="h-full flex-1" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.ui.loading-overlay label="Loading your achievements" />
    @elseif ($screenState === 'error')
        <x-native.ui.error-state
            :description="$screenError"
            retry-label="Retry achievements"
            retry-method="retryAchievements"
        />
    @else
    <x-native.ui.app-header
        eyebrow="Your progress"
        title="Badges"
        :streak="$streakLabel"
        :level="$level"
    />

    {{-- Hero: total badges + Bronze/Silver/Gold tier gauges. Counts sit dead
         centre and the dial font auto-scales, so higher counts never overflow. --}}
    <native:column class="w-full rounded-3xl bg-linear-to-br from-lime-400/40 via-cyan-500/18 to-transparent border border-lime-400/50 shadow-lg py-5 px-4 gap-4" :animate-duration="$motionDuration">
        <native:column class="w-full gap-1">
            <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-accent">Badges earned</native:text>
            <native:row class="items-end gap-1.5">
                <native:text font="numeric" class="text-[34] leading-none text-theme-primary-text">{{ $totalEarned }}</native:text>
                <native:text class="text-[14] font-semibold text-theme-muted-text">/ {{ $totalBadges }}</native:text>
            </native:row>
        </native:column>

        <x-native.ui.progress :value="$totalProgress" token="accent" />

        <native:row class="w-full gap-2">
            @foreach ($tierSummary as $tier)
                @php
                    $gaugeColor = match (strtolower($tier['label'])) {
                        'bronze' => '#D08A5C',
                        'silver' => '#C3C8D0',
                        'gold' => '#E7C24B',
                        default => '#C5DB55',
                    };
                @endphp
                <native:column class="flex-1 items-center">
                    <x-native.games.shared.gauge
                        :value="$tier['earned']"
                        :fraction="$tier['total'] > 0 ? $tier['earned'] / $tier['total'] : 0"
                        :color="$gaugeColor"
                        :label="$tier['label']"
                        size="w-16 h-16"
                    />
                </native:column>
            @endforeach
        </native:row>
    </native:column>

    {{-- Per-category badge cards --}}
    <x-native.dashboard.section-header
        title="Categories"
        eyebrow="EARN BRONZE, THEN SILVER, THEN GOLD"
    />

    @foreach ($categories as $category)
        <x-native.badges.category-card
            :category="$category"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
            :motion-duration="$motionDuration"
        />
    @endforeach

    {{-- Underlying training stats --}}
    <x-native.dashboard.section-header title="Your stats" />

    <native:column class="w-full rounded-3xl bg-theme-surface shadow-lg border {{ Gradients::hairline() }} p-4 gap-3">
        <native:row class="gap-3">
            <x-native.games.shared.stat
                :ios="Ios::Flame"
                :android="AndroidOutlined::LocalFireDepartment"
                label="Day streak"
                :value="$streakLabel"
            />
            <x-native.games.shared.stat
                :ios="Ios::Target"
                :android="AndroidOutlined::GpsFixed"
                label="Accuracy"
                :value="$accuracyLabel"
            />
        </native:row>
        <native:row class="gap-3">
            <x-native.games.shared.stat
                :ios="Ios::Bolt"
                :android="AndroidOutlined::Bolt"
                label="Avg. speed"
                :value="$speedLabel"
            />
            <x-native.games.shared.stat
                :ios="Ios::Gamecontroller"
                :android="AndroidOutlined::SportsEsports"
                label="Games played"
                :value="$gamesLabel"
            />
        </native:row>
        <native:row class="gap-3">
            <x-native.games.shared.stat
                :ios="Ios::Crown"
                :android="AndroidOutlined::WorkspacePremium"
                label="Best score"
                :value="$bestLabel"
            />
        </native:row>
    </native:column>
    @endif
</native:column>
</native:scroll-view>
</native:column>
