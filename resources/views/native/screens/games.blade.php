<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1" :shows-indicators="false">
<native:column class="w-full px-4 mt-2 mb-12 gap-4">
    @if ($libraryState === 'loading')
        <x-native.ui.loading-overlay label="Loading the games library" />
    @elseif ($libraryState === 'error')
        <x-native.ui.error-state
            :description="$libraryError"
            retry-label="Retry games library"
            retry-method="retryLibrary"
        />
    @else
        <x-native.ui.app-header
            :eyebrow="count($playableGames).' games'"
            title="Games"
            :streak="$currentStreak"
            :level="$level"
        />

        {{-- Category filter chips --}}
        <native:scroll-view horizontal :shows-indicators="false" class="w-full">
            <native:row class="items-center gap-2 pr-4">
                @foreach ($categories as $category)
                    <native:pressable
                        class="rounded-full px-4 py-2 border {{ $selectedCategory === $category['key'] ? 'bg-linear-to-r from-lime-400 to-cyan-400 border-transparent' : 'bg-theme-surface border-theme-border' }}"
                        :press-scale="0.97"
                        a11y-label="{{ $category['label'] }} games"
                        @press="setCategory('{{ $category['key'] }}')"
                    >
                        <native:text class="text-[13] font-semibold {{ $selectedCategory === $category['key'] ? 'text-black' : 'text-theme-secondary-text' }}">{{ $category['label'] }}</native:text>
                    </native:pressable>
                @endforeach
            </native:row>
        </native:scroll-view>

        {{-- Featured game hero (only in the unfiltered 'all' view) --}}
        @if ($selectedCategory === 'all' && ! empty($featuredGame))
            <x-native.dashboard.section-header title="Featured" />
            <x-native.dashboard.play-card
                :slug="$featuredGame['slug']"
                :title="$featuredGame['title']"
                :subtitle="($featuredGame['best_score'] ?? null) === null ? 'Recommended for you' : 'Best '.number_format($featuredGame['best_score']).' · Recommended'"
                :press-scale="$pressScale"
                :press-opacity="$pressOpacity"
                :motion-duration="$motionDuration"
            />
        @endif

        {{-- All games as rich wide rows --}}
        <x-native.dashboard.section-header :title="$selectedCategory === 'all' ? 'All games' : 'Filtered'" />
        <native:column class="w-full gap-2.5">
            @foreach ($filteredPlayableGames as $game)
                @continue($selectedCategory === 'all' && ! empty($featuredGame) && $game['slug'] === $featuredGame['slug'])
                <x-native.games.shared.game-row :game="$game" />
            @endforeach
        </native:column>
    @endif
</native:column>
</native:scroll-view>
</native:column>
