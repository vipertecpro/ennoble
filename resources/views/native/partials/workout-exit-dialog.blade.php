<native:modal
    :visible="$dialogVisible"
    :dismissible="true"
    @dismiss="dismissDialog"
>
    <native:column class="gap-5 rounded-3xl bg-theme-surface-elevated p-5">
        <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">Leave workout?</native:text>
        <native:text class="text-[17] leading-relaxed text-theme-secondary-text">
            Your progress checkpoint will remain on this device so you can resume later.
        </native:text>
        <native:button label="Keep Training" size="lg" variant="primary" @press="cancelExit" />
        <native:button label="Exit to Home" size="lg" variant="destructive" @press="confirmExit" />
    </native:column>
</native:modal>
