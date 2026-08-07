@use('App\NativeUI\Tokens\Gradients')
@use('App\Icons\Ios')
@use('App\Icons\AndroidOutlined')

<native:column class="h-full w-full {{ Gradients::screen() }}">
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
            <native:column class="w-full px-4 mt-4 pb-6 gap-6">
                <native:row class="w-full items-stretch gap-3">
                    <x-native.ui.stat-badge
                        :value="$bestScore === null ? '—' : number_format($bestScore)"
                        label="Your best"
                        accent="rose-500"
                        labelColor="rose-500"
                    />
                    {{-- Extra padding compensates for the smaller value size so
                         this badge matches its sibling's height — `items-stretch`
                         does not equalise them on iOS. --}}
                    <x-native.ui.stat-badge
                        :value="$difficultyLabel"
                        label="Level"
                        accent="amber-400"
                        labelColor="amber-400"
                        valueSize="text-[20]"
                        padding="py-7 px-3"
                    />
                </native:row>

                <native:column class="w-full gap-3">
                    <x-native.dashboard.section-header title="How to play" />
                    <native:column class="w-full rounded-3xl bg-theme-surface shadow-lg border {{ Gradients::hairline() }} w-full gap-3 px-4 py-4">
                        @foreach ($steps as $index => $step)
                            <native:row class="w-full items-start gap-3">
                                <native:column class="w-6 h-6 items-center justify-center rounded-full bg-linear-to-br from-rose-500 to-orange-400 shadow-sm">
                                    <native:text class="text-[12] font-bold text-black">{{ $index + 1 }}</native:text>
                                </native:column>
                                <native:text class="flex-1 text-[13] leading-relaxed text-theme-primary-text">{{ $step }}</native:text>
                            </native:row>
                        @endforeach
                    </native:column>
                </native:column>

                @if (count($skills) > 0)
                    <native:column class="w-full gap-2">
                        <x-native.dashboard.section-header title="Trains" />
                        <native:row class="w-full items-center gap-2">
                            @foreach ($skills as $skill)
                                <native:column class="rounded-full bg-linear-to-r from-rose-500/20 to-orange-400/10 border {{ Gradients::hairline() }} px-3 py-1">
                                    <native:text class="text-[12] font-semibold text-theme-primary-text">{{ $skill }}</native:text>
                                </native:column>
                            @endforeach
                        </native:row>
                    </native:column>
                @endif

                @if (count($history) > 0)
                    <native:column class="w-full gap-2">
                        <x-native.dashboard.section-header title="Recent results" />
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

        {{-- No `safe-area-bottom` here. This screen renders under the layout's
             chrome, which already insets the home indicator — adding the class
             on top of that doubled it and left the button floating ~52pt off the
             edge. `pb-2` alone measures 42pt to the screen edge: 34pt of system
             inset plus 8pt of breathing room. --}}
        <native:column class="w-full px-4 pt-3 pb-2">
            <x-native.ui.gradient-button label="Play" press="play" />
        </native:column>
    @endif
</native:column>
