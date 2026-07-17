@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$shellState" :safe-area="true">
    <native:column class="w-full h-full items-center justify-center gap-5">
        <native:column class="w-20 h-20 items-center justify-center rounded-3xl bg-theme-primary">
            <x-native.icon
                :ios="Ios::Brain"
                :android="AndroidOutlined::Psychology"
                :size="44"
                a11y-label="Ennoble"
            />
        </native:column>
        <native:text class="text-4xl font-bold text-center text-theme-on-background">Ennoble</native:text>
        <native:text class="text-base leading-relaxed text-center text-theme-on-surface-variant">
            Native application shell ready.
        </native:text>
        <native:button label="Enter Ennoble" size="lg" @press="enterApplication" />
    </native:column>
</x-native.screen-container>
