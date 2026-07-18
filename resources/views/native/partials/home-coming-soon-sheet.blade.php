<native:bottom-sheet
    :visible="$bottomSheetVisible"
    detents="medium,large"
    @dismiss="dismissBottomSheet"
>
    <native:column class="gap-4 p-5">
        <native:text class="text-xs font-semibold text-theme-accent">COMING SOON</native:text>
        <native:text class="text-2xl font-bold text-theme-primary-text">{{ $comingSoonTitle }}</native:text>
        <native:text class="text-base leading-relaxed text-theme-secondary-text">
            {{ $comingSoonDescription }}
        </native:text>
        <native:text class="text-sm leading-relaxed text-theme-secondary-text">
            This preview is informational only. It does not create a session or open gameplay.
        </native:text>
        <native:button label="Got it" variant="secondary" @press="dismissBottomSheet" />
    </native:column>
</native:bottom-sheet>
