<native:modal
    :visible="$dialogVisible"
    :dismissible="true"
    @dismiss="dismissDialog"
>
    <native:column class="w-full gap-5 bg-theme-surface p-5">
        <native:text class="text-2xl font-bold text-theme-on-surface">Leave workout?</native:text>
        <native:text class="text-base leading-relaxed text-theme-on-surface-variant">
            Your current placeholder checkpoint will remain on this device so you can resume later.
        </native:text>
        <native:button label="Keep Training" size="lg" variant="primary" @press="cancelExit" />
        <native:button label="Exit to Home" size="lg" variant="destructive" @press="confirmExit" />
    </native:column>
</native:modal>
