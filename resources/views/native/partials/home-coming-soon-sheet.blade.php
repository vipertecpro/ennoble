<native:bottom-sheet
    :visible="$bottomSheetVisible"
    detents="medium,large"
    @dismiss="dismissBottomSheet"
>
    <native:column class="w-full gap-4 p-5">
        <native:text class="text-xs font-semibold text-theme-primary">COMING SOON</native:text>
        <native:text class="text-2xl font-bold text-theme-on-surface">{{ $comingSoonTitle }}</native:text>
        <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
            {{ $comingSoonDescription }}
        </native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
            This preview is informational only. It does not create a session or open gameplay.
        </native:text>
        <native:button label="Got it" variant="secondary" @press="dismissBottomSheet" />
    </native:column>
</native:bottom-sheet>
