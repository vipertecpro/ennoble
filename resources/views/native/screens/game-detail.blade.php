@use('App\Icons\Ios')
@use('App\Icons\AndroidOutlined')

<native:column class="h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-4">
            <native:text class="text-[22] font-bold text-center text-theme-primary-text">Game unavailable</native:text>
            <native:text class="text-[15] leading-relaxed text-center text-theme-secondary-text">
                This game could not be loaded right now.
            </native:text>
            <native:button class="w-full" label="Back to games" size="lg" variant="primary" @press="backToGames" />
        </native:column>
    @else
        <native:scroll-view class="flex-1 w-full bg-theme-background" :shows-indicators="false">
            <native:column class="w-full px-4 pt-4 pb-6 gap-6">
                <native:row class="w-full gap-3">
                    <native:column class="flex-1 items-center gap-1 rounded-2xl bg-theme-surface shadow-sm py-4">
                        <native:text class="text-[22] font-bold text-theme-primary-text">
                            {{ $bestScore === null ? '—' : number_format($bestScore) }}
                        </native:text>
                        <native:text class="text-[12] text-theme-muted-text">Your best</native:text>
                    </native:column>
                    <native:column class="flex-1 items-center gap-1 rounded-2xl bg-theme-surface shadow-sm py-4">
                        <native:text class="text-[22] font-bold text-theme-primary-text">{{ $difficultyLabel }}</native:text>
                        <native:text class="text-[12] text-theme-muted-text">Level</native:text>
                    </native:column>
                </native:row>

                <native:column class="w-full gap-3">
                    <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-muted-text">
                        How to play
                    </native:text>
                    <native:column class="w-full gap-3 rounded-2xl bg-theme-surface shadow-sm px-4 py-4">
                        @foreach ($steps as $index => $step)
                            <native:row class="w-full items-start gap-3">
                                <native:column class="w-6 h-6 items-center justify-center rounded-full bg-theme-primary-surface">
                                    <native:text class="text-[13] font-bold text-theme-accent">{{ $index + 1 }}</native:text>
                                </native:column>
                                <native:text class="flex-1 text-[15] leading-relaxed text-theme-primary-text">{{ $step }}</native:text>
                            </native:row>
                        @endforeach
                    </native:column>
                </native:column>

                @if (count($skills) > 0)
                    <native:column class="w-full gap-2">
                        <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-muted-text">
                            Trains
                        </native:text>
                        <native:row class="w-full items-center gap-2">
                            @foreach ($skills as $skill)
                                <native:column class="rounded-full bg-theme-secondary-surface px-3 py-1">
                                    <native:text class="text-[13] font-semibold text-theme-secondary-text">{{ $skill }}</native:text>
                                </native:column>
                            @endforeach
                        </native:row>
                    </native:column>
                @endif
            </native:column>
        </native:scroll-view>

        <native:column class="w-full px-4 pt-3 pb-8 bg-theme-background">
            <native:button
                class="w-full"
                label="Play"
                size="lg"
                variant="primary"
                a11y-hint="Starts a new {{ $title }} session"
                @press="play"
            />
        </native:column>
    @endif
</native:column>
