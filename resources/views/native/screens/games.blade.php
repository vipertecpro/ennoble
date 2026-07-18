@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$libraryState" :scroll="true">
    @if ($libraryState === 'loading')
        <x-native.loading-overlay label="Loading the offline games library" />
    @elseif ($libraryState === 'error')
        <x-native.error-state :description="$libraryError">
            <x-slot:retry>
                <native:button label="Retry games library" variant="secondary" @press="retryLibrary" />
            </x-slot:retry>
        </x-native.error-state>
    @else
    <native:column class="w-full gap-2">
        <native:text class="text-xs font-semibold text-theme-primary">CURATED TRAINING</native:text>
        <native:text class="text-3xl font-bold leading-tight text-theme-on-background">Train with purpose.</native:text>
        <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
            Choose a focused experience, understand what it trains, and see only progress backed by your local history.
        </native:text>
    </native:column>

    <x-native.games-search-input :search-query="$searchQuery" />

    <native:column class="w-full gap-2" a11y-label="Game category filters">
        @foreach (array_chunk($categories, 3) as $categoryRow)
            <native:row ref="game-filter-row-{{ $loop->iteration }}" class="w-full gap-2">
                @foreach ($categoryRow as $category)
                    <x-native.game-filter-chip
                        :category="$category['key']"
                        :label="$category['label']"
                        :selected="$selectedCategory === $category['key']"
                    />
                @endforeach
            </native:row>
        @endforeach
    </native:column>

    @if ($statisticsLoading)
        <x-native.dashboard-loading-card label="Loading game statistics" />
    @elseif ($statisticsError)
        <native:column class="w-full gap-3 rounded-2xl border border-theme-outline bg-theme-surface p-4">
            <native:text class="text-base font-semibold text-theme-on-surface">Statistics unavailable</native:text>
            <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">{{ $statisticsError }}</native:text>
            <native:button label="Retry statistics" variant="secondary" @press="retryStatistics" />
        </native:column>
    @endif

    @if (! $featuredVisible && $filteredPlayableGames === [] && $filteredComingSoonGames === [])
        <x-native.empty-state
            :ios="Ios::Magnifyingglass"
            :android="AndroidOutlined::SearchOff"
            :title="$emptyTitle"
            :description="$emptyDescription"
        >
            <x-slot:action>
                @if ($selectedCategory !== 'all')
                    <native:button label="Show all games" variant="secondary" @press="showAllGames" />
                @endif
            </x-slot:action>
        </x-native.empty-state>
    @else
        @if ($featuredVisible && $featuredGame)
            <x-native.dashboard-section-header title="Featured" eyebrow="A FOCUSED PLACE TO BEGIN" />
            <x-native.featured-game-card
                :game="$featuredGame"
                :motion-duration="$motionDuration"
            />
        @endif

        @if ($filteredPlayableGames !== [])
            <x-native.dashboard-section-header title="Available Games" eyebrow="PLAYABLE IN THE FIRST RELEASE" />

            @foreach ($filteredPlayableGames as $game)
                <x-native.game-card
                    :game="$game"
                    :motion-duration="$motionDuration"
                />
            @endforeach
        @endif

        @if ($filteredComingSoonGames !== [])
            <x-native.dashboard-section-header title="Coming Soon" eyebrow="INFORMATIONAL PREVIEWS" />

            @foreach ($filteredComingSoonGames as $game)
                <x-native.coming-soon-game-card
                    :game="$game"
                    :press-scale="$pressScale"
                    :press-opacity="$pressOpacity"
                    :motion-duration="$motionDuration"
                />
            @endforeach
        @endif
    @endif
    @endif

    @if ($bottomSheetVisible)
        @include('native.partials.games-coming-soon-sheet')
    @endif
</x-native.screen-container>
