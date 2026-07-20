@use('App\Icons\Ios')
@use('App\Icons\Android')

@php
    $visibleSteps = array_slice($steps, 0, $revealedCount);
    $typing = $revealedCount < count($steps);
@endphp

<native:column class="h-full w-full bg-theme-background safe-area">
    <native:row class="w-full px-4 pt-3 pb-2 items-center gap-3">
        <native:pressable
            @press="close"
            a11y-label="Close explanation"
            :press-scale="0.9"
            class="w-9 h-9 items-center justify-center rounded-full bg-theme-surface"
        >
            <x-native.ui.icon :ios="Ios::Xmark" :android="Android::Close" :size="16" />
        </native:pressable>

        <native:column class="flex-1">
            <native:text class="text-[16] font-bold text-theme-primary-text">Explanation</native:text>
            <native:text class="text-[12] text-theme-muted-text">{{ $expression }} = {{ number_format($answer) }}</native:text>
        </native:column>
    </native:row>

    <native:divider class="border-theme-divider" />

    <native:scroll-view class="flex-1 w-full" scroll-anchor="bottom">
        <native:column class="w-full px-4 py-4 gap-3">
            @foreach ($visibleSteps as $index => $step)
                <native:row
                    native:key="qm-msg-{{ $index }}"
                    class="w-full items-end gap-2"
                    :translate-y="$reducedMotion ? 0 : 10"
                    :opacity="$reducedMotion ? 1 : 0.85"
                    :animate-duration="$motionDuration"
                    animate-easing="ease-out"
                >
                    <native:column class="w-8 h-8 items-center justify-center rounded-full bg-theme-primary-surface">
                        <x-native.ui.icon :ios="Ios::Sparkles" :android="Android::AutoAwesome" :size="15" />
                    </native:column>

                    <native:column class="rounded-2xl bg-theme-surface shadow-sm px-4 py-3 max-w-[80%]">
                        <native:text class="text-[15] leading-relaxed text-theme-primary-text">{{ $step }}</native:text>
                    </native:column>
                </native:row>
            @endforeach

            @if ($typing)
                <native:row class="w-full items-end gap-2" a11y-label="Assistant is typing">
                    <native:column class="w-8 h-8 items-center justify-center rounded-full bg-theme-primary-surface">
                        <x-native.ui.icon :ios="Ios::Sparkles" :android="Android::AutoAwesome" :size="15" />
                    </native:column>

                    <native:row class="items-center rounded-2xl bg-theme-surface shadow-sm px-4 py-4 gap-2">
                        @for ($dot = 0; $dot < 3; $dot++)
                            <native:circle
                                :width="7"
                                :height="7"
                                class="rounded-full bg-theme-muted-text"
                                :opacity="0.4"
                                :scale="$reducedMotion ? 1 : 1.4"
                                :animate-duration="$reducedMotion ? 0 : 600"
                                animate-easing="ease-in-out"
                                animate-loop
                            />
                        @endfor
                    </native:row>
                </native:row>
            @endif
        </native:column>
    </native:scroll-view>

    @unless ($typing)
        <native:column
            native:key="qm-explain-actions"
            class="w-full px-4 pb-6 pt-2 gap-2"
            :translate-y="$reducedMotion ? 0 : 14"
            :opacity="$reducedMotion ? 1 : 0.8"
            :animate-duration="$motionDuration"
            animate-easing="ease-out"
        >
            <native:button class="w-full" label="Got it" size="lg" variant="primary" @press="close" />
        </native:column>
    @endunless
</native:column>
