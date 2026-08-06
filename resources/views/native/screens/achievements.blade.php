@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background safe-area-top">
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
    {{-- Hero: total badges + tier summary — vibrant lime→cyan gradient --}}
    <native:column class="w-full items-center rounded-3xl bg-linear-to-br from-lime-400/30 via-cyan-500/15 to-transparent border border-lime-400/40 shadow-lg py-6" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 gap-5">
        <native:text class="text-[11] font-semibold uppercase tracking-widest text-lime-400">BADGES EARNED</native:text>

        <native:row class="items-end gap-2">
            <native:text class="text-[34] font-bold tracking-tight leading-tight text-theme-primary-text">{{ $totalEarned }}</native:text>
            <native:text class="text-[15] font-semibold text-theme-muted-text mb-1">/ {{ $totalBadges }}</native:text>
        </native:row>

        <native:progress-bar
            :value="$totalProgress"
            a11y-label="{{ $totalEarned }} of {{ $totalBadges }} badges earned"
        />

        <native:row class="items-center justify-between">
            @foreach ($tierSummary as $tier)
                <x-native.badges.tier-pill
                    :label="$tier['label']"
                    :earned="$tier['earned']"
                    :total="$tier['total']"
                    :color="$tier['color']"
                />
            @endforeach
        </native:row>
    </native:column>
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

    <x-native.ui.glow-card class="p-4 gap-3">
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
    </x-native.ui.glow-card>
    @endif
</native:column>
</native:scroll-view>
</native:column>
