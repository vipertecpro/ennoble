@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-5 safe-area">
    <native:column class="items-center justify-center gap-5">
        <native:column class="w-20 h-20 items-center justify-center rounded-2xl bg-theme-primary-surface">
            <x-native.ui.icon
                :ios="Ios::Brain"
                :android="AndroidOutlined::Psychology"
                :size="44"
                a11y-label="Ennoble"
            />
        </native:column>
        <native:text class="text-[26] font-bold tracking-tight text-center text-theme-primary-text">Ennoble</native:text>
        <native:text class="text-[15] leading-relaxed text-center text-theme-secondary-text">
            A private daily practice for a clearer mind.
        </native:text>
        <native:button label="Enter Ennoble" size="lg" @press="enterApplication" />
    </native:column>
</native:column>
</native:scroll-view>
</native:column>
