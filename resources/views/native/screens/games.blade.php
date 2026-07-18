@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
    @if ($libraryState === 'loading')
        <x-native.loading-overlay label="Loading the offline games library" />
    @elseif ($libraryState === 'error')
        <x-native.error-state
            :description="$libraryError"
            retry-label="Retry games library"
            retry-method="retryLibrary"
        />
    @else
    <native:column class="gap-2">
        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">CURATED TRAINING</native:text>
        <native:text class="text-[28] font-bold tracking-tight leading-tight text-theme-primary-text">Train with purpose.</native:text>
        <native:text class="text-[17] leading-relaxed text-theme-secondary-text">
            Choose a focused experience, understand what it trains, and see only progress backed by your local history.
        </native:text>
    </native:column>

    <x-native.games-search-input :search-query="$searchQuery" />

    <native:column class="gap-2" a11y-label="Game category filters">
        @foreach (array_chunk($categories, 3) as $categoryRow)
            <native:row ref="game-filter-row-{{ $loop->iteration }}" class="gap-2">
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
        <native:column class="gap-3 rounded-2xl bg-theme-surface shadow-sm p-4">
            <native:text class="text-[17] font-semibold text-theme-primary-text">Statistics unavailable</native:text>
            <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ $statisticsError }}</native:text>
            <native:button label="Retry statistics" variant="secondary" @press="retryStatistics" />
        </native:column>
    @endif

    @if (! $featuredVisible && $filteredPlayableGames === [])
        <x-native.empty-state
            :ios="Ios::Magnifyingglass"
            :android="AndroidOutlined::SearchOff"
            :title="$emptyTitle"
            :description="$emptyDescription"
            :action-label="$selectedCategory !== 'all' ? 'Show all games' : null"
            :action-method="$selectedCategory !== 'all' ? 'showAllGames' : null"
        />
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
    @endif
    @endif
</native:column>
</native:row>
</native:scroll-view>
</native:column>
