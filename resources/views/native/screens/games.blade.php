<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1" :shows-indicators="false">
<native:column class="w-full px-4 mt-4 mb-12 gap-4">
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

        @foreach (array_chunk($playableGames, 2) as $gameRow)
            <native:row class="w-full items-stretch gap-3">
                @foreach ($gameRow as $game)
                    <x-native.games.shared.tile :game="$game" :motion-duration="$motionDuration" />
                @endforeach

                @if (count($gameRow) === 1)
                    <native:column class="flex-1" />
                @endif
            </native:row>
        @endforeach
    @endif
</native:column>
</native:scroll-view>
</native:column>
