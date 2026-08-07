@use('App\Icons\Android')
@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')

<native:column class="h-full w-full {{ Gradients::screen() }}">
    {{-- Pinned above the scroll-view so the quotes stay put while Home scrolls.
         NO `ios:safe-area-top` here, and none on the scroll content below
         either: the ticker is itself a scroll-view, and a scroll-view already
         insets below the status bar on iOS. Adding the class on top of that
         double-inserts and drops the ticker ~70pt down the screen (measured).
         The root stays full-bleed so the canvas still paints behind the status
         bar. --}}
    @if ($screenState !== 'error')
        <native:column class="w-full">
            <native:quote-ticker />
        </native:column>
    @endif

    <native:scroll-view class="h-full flex-1" :shows-indicators="false">
        <native:column class="w-full mt-5 mb-12 gap-4">
            @if ($screenState === 'loading')
                <native:column class="w-full px-4">
                    <x-native.ui.loading-overlay label="Loading your home screen"/>
                </native:column>
            @elseif ($screenState === 'error')
                <native:column class="w-full px-4">
                    <x-native.ui.error-state
                        :description="$screenError"
                        retry-label="Retry"
                        retry-method="retryHome"
                    />
                </native:column>
            @else
                <x-native.dashboard.home-header
                    :greeting="$greeting"
                    :display-name="$displayName"
                    :today-label="$todayLabel"
                    :current-streak="$currentStreak"
                    :level="$level"
                    :level-title="$levelTitle"
                    :level-progress="$levelProgress"
                    :xp-label="$xpLabel"
                    :hour="$hour"
                    :minute="$minute"
                    :meridiem="$meridiem"
                    :motion-duration="$motionDuration"
                />

                {{-- Everything below the masthead sits back inside the gutter. --}}
                <native:column class="w-full px-4 gap-4">

                    {{-- Stat strip. No Streak chip: the header states the streak in
                         full, and repeating it here read as two answers to the same
                         question. --}}
                    <native:row class="w-full gap-4" :animate-duration="$motionDuration">
                        <x-native.ui.stat-chip
                            :value="$gamesPlayed"
                            label="Games"
                            token="accent"
                            lottie="gaming"
                            :ios="Ios::GamecontrollerFill"
                            :android="Android::SportsEsports"
                        />
                        <x-native.ui.stat-chip
                            :value="$accuracyLabel"
                            label="Accuracy"
                            token="accent-cyan"
                            lottie="target"
                            :ios="Ios::Target"
                            :android="AndroidOutlined::GpsFixed"
                        />
                    </native:row>

                    {{-- Your games — horizontal carousel --}}
                    @if (count($games) > 0)
                        <x-native.dashboard.section-header title="Your games"/>
                        <native:scroll-view horizontal :shows-indicators="false" class="w-full">
                            <native:row class="items-stretch gap-4 pr-4">
                                @foreach ($games as $index => $game)
                                    <x-native.games.shared.game-card :game="$game" :delay="$reducedMotion ? 0 : min($index, 6) * 45"/>
                                @endforeach
                            </native:row>
                        </native:scroll-view>
                    @endif

                    {{-- Latest badge --}}
                    <native:column
                        class="w-full rounded-2xl bg-theme-surface-elevated border {{ Gradients::hairline() }} overflow-hidden">
                        <x-native.ui.list-row
                            :ios="Ios::Rosette"
                            :android="AndroidOutlined::MilitaryTech"
                            iconSolid="bg-linear-to-br from-amber-400 to-orange-500"
                            :title="$achievementTitle ?? 'No badges yet'"
                            :subtitle="$achievementTitle ? 'Latest unlock' : 'Play to earn your first'"
                            chevron
                            method="openAchievements"
                        />
                    </native:column>
                </native:column>
            @endif
        </native:column>
    </native:scroll-view>
</native:column>
