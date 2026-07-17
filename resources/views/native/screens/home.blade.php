@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$shellState">
    <x-slot:loading>
        <x-native.loading-overlay label="Loading Home" />
    </x-slot:loading>
    <x-slot:empty>
        <x-native.empty-state
            :ios="Ios::House"
            :android="AndroidOutlined::Home"
            title="Home shell ready"
            description="Product content begins in a later prompt."
        />
    </x-slot:empty>
    <x-slot:error>
        <x-native.error-state :description="$errorMessage">
            <x-slot:retry>
                <native:button label="Retry" variant="secondary" @press="retry" />
            </x-slot:retry>
        </x-native.error-state>
    </x-slot:error>
    <x-slot:overlays>
        <x-native.dialog-host
            :dialog-visible="$dialogVisible"
            :bottom-sheet-visible="$bottomSheetVisible"
        >
            <x-slot:dialog>
                <native:column class="gap-4 p-5">
                    <native:text class="text-xl font-semibold text-theme-on-surface">Alert title</native:text>
                    <native:text class="text-base text-theme-on-surface-variant">Reusable rich alert content.</native:text>
                </native:column>
            </x-slot:dialog>
            <x-slot:sheet>
                <native:column class="gap-4 p-5">
                    <native:text class="text-xl font-semibold text-theme-on-surface">Sheet title</native:text>
                    <native:text class="text-base text-theme-on-surface-variant">Reusable bottom-sheet content.</native:text>
                </native:column>
            </x-slot:sheet>
        </x-native.dialog-host>
    </x-slot:overlays>
</x-native.screen-container>
