@use('App\NativeUI\Tokens\Gradients')
@use('App\Icons\Ios')
@use('App\Icons\AndroidOutlined')

<native:column class="h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-4">
            <native:text class="text-[18] font-bold text-center text-theme-primary-text">Game unavailable</native:text>
            <native:text class="text-[13] leading-relaxed text-center text-theme-secondary-text">
                This game could not be loaded right now.
            </native:text>
            <x-native.ui.gradient-button label="Back to games" press="backToGames" />
        </native:column>
    @else
        <native:scroll-view class="flex-1 w-full" :shows-indicators="false">
            <native:column class="w-full px-4 pt-4 pb-6 gap-6">
                <native:row class="w-full items-stretch gap-3">
                    <x-native.ui.stat-badge
                        :value="$bestScore === null ? '—' : number_format($bestScore)"
                        label="Your best"
                        accent="lime-400"
                        accentTo="cyan-500"
                        labelColor="lime-400"
                    />
                    <x-native.ui.stat-badge
                        :value="$difficultyLabel"
                        label="Level"
                        accent="amber-400"
                        accentTo="orange-500"
                        labelColor="amber-400"
                        valueSize="text-[20]"
                    />
                </native:row>

                <native:column class="w-full gap-3">
                    <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
                        How to play
                    </native:text>
                    <native:column class="w-full rounded-3xl bg-theme-surface shadow-lg border border-lime-400/40 w-full gap-3 px-4 py-4">
                        @foreach ($steps as $index => $step)
                            <native:row class="w-full items-start gap-3">
                                <native:column class="w-6 h-6 items-center justify-center rounded-full bg-linear-to-br from-lime-400 to-cyan-400 shadow-sm">
                                    <native:text class="text-[12] font-bold text-black">{{ $index + 1 }}</native:text>
                                </native:column>
                                <native:text class="flex-1 text-[13] leading-relaxed text-theme-primary-text">{{ $step }}</native:text>
                            </native:row>
                        @endforeach
                    </native:column>
                </native:column>

                @if (count($skills) > 0)
                    <native:column class="w-full gap-2">
                        <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
                            Trains
                        </native:text>
                        <native:row class="w-full items-center gap-2">
                            @foreach ($skills as $skill)
                                <native:column class="rounded-full bg-linear-to-r from-lime-400/20 to-cyan-500/10 border border-lime-400/30 px-3 py-1">
                                    <native:text class="text-[12] font-semibold text-theme-primary-text">{{ $skill }}</native:text>
                                </native:column>
                            @endforeach
                        </native:row>
                    </native:column>
                @endif

                @if (count($history) > 0)
                    <native:column class="w-full gap-2">
                        <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
                            Recent results
                        </native:text>
                        <native:column class="w-full rounded-3xl bg-theme-surface shadow-lg border {{ Gradients::hairline() }} w-full">
                            @foreach ($history as $result)
                                <native:column class="w-full px-4 py-3 gap-1">
                                    <native:row class="w-full items-center justify-between">
                                        <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $result['score'] }} pts</native:text>
                                        <native:text class="text-[12] text-theme-muted-text">{{ $result['date'] }}</native:text>
                                    </native:row>
                                    <native:row class="w-full items-center gap-4">
                                        <native:text class="text-[11] text-theme-secondary-text">{{ $result['correct'] }} correct</native:text>
                                        <native:text class="text-[11] text-theme-secondary-text">{{ $result['accuracy'] }} acc</native:text>
                                        <native:text class="text-[11] text-theme-secondary-text">{{ $result['avg'] }}/q</native:text>
                                        <native:text class="text-[11] text-theme-secondary-text">{{ $result['duration'] }}</native:text>
                                    </native:row>
                                </native:column>

                                @unless ($loop->last)
                                    <native:divider />
                                @endunless
                            @endforeach
                        </native:column>
                    </native:column>
                @endif
            </native:column>
        </native:scroll-view>

        <native:column class="w-full px-4 pt-3 pb-4 bg-theme-background safe-area-bottom">
            <x-native.ui.gradient-button label="Play" press="play" />
        </native:column>
    @endif
</native:column>
