<native:bottom-sheet
    :visible="$bottomSheetVisible"
    detents="medium,large"
    @dismiss="dismissBottomSheet"
>
    <native:column class="w-full gap-4 p-5">
        <native:row class="w-full flex-wrap items-center gap-2">
            <x-native.game-badge label="COMING SOON" :emphasis="true" :motion-duration="$motionDuration" />
            <native:text class="text-sm font-semibold text-theme-on-surface-variant">
                {{ $comingSoonCategory }} · {{ $comingSoonDuration }}
            </native:text>
        </native:row>
        <native:text class="text-2xl font-bold text-theme-on-surface">{{ $comingSoonTitle }}</native:text>
        <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
            {{ $comingSoonDescription }}
        </native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
            This game is unavailable today. This sheet is informational only and does not create a session or open gameplay.
        </native:text>
        <native:button label="Got it" variant="secondary" @press="dismissBottomSheet" />
    </native:column>
</native:bottom-sheet>
