<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($screenState === 'error')
        <x-native.ui.error-state
            description="This badge category could not be found."
            retry-label="Back to achievements"
            retry-method="backToAchievements"
        />
    @else
    {{-- Category summary — vibrant lime→cyan gradient hero --}}
    <native:column class="w-full rounded-3xl bg-linear-to-br from-lime-400/30 via-cyan-500/15 to-transparent border border-lime-400/40 shadow-lg py-6" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 gap-2">
        <native:text class="text-[11] font-semibold uppercase tracking-widest text-lime-400">{{ $earnedCount }} OF {{ $totalCount }} EARNED</native:text>
        <native:text class="text-[18] font-semibold tracking-tight leading-tight text-theme-primary-text">{{ $categoryTagline }}</native:text>
        <native:text class="text-[13] leading-relaxed text-theme-secondary-text">Now: {{ $currentLabel }}</native:text>
    </native:column>
    </native:column>

    {{-- Badge wall, grouped by tier --}}
    @foreach ($tierGroups as $group)
        <x-native.dashboard.section-header
            :title="$group['label']"
            eyebrow="{{ $group['earned'] }} OF {{ $group['total'] }} EARNED"
        />

        <x-native.ui.glow-card :accent="'theme-'.$group['color']" class="p-4 gap-4">
            @foreach (array_chunk($group['badges'], 4) as $row)
                <native:row class="items-start gap-3">
                    @foreach ($row as $badge)
                        <native:column class="flex-1 items-center gap-2">
                            <x-native.badges.medal
                                :color="$group['color']"
                                :tier-label="$group['label']"
                                :unlocked="$badge['unlocked']"
                                size="sm"
                            />
                            <native:text class="text-[11] font-semibold text-center leading-tight text-theme-primary-text">
                                {{ $badge['thresholdLabel'] }}
                            </native:text>
                        </native:column>
                    @endforeach

                    @for ($i = count($row); $i < 4; $i++)
                        <native:column class="flex-1" />
                    @endfor
                </native:row>
            @endforeach
        </x-native.ui.glow-card>
    @endforeach
    @endif
</native:column>
</native:scroll-view>
</native:column>
