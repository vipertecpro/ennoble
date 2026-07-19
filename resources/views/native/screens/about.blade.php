@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    <native:column class="items-center gap-4">
        <native:column class="w-20 h-20 items-center justify-center rounded-2xl bg-theme-primary-surface">
            <x-native.icon
                :ios="Ios::BrainHeadProfile"
                :android="AndroidOutlined::Psychology"
                :size="40"
                a11y-label="Ennoble"
            />
        </native:column>

        <native:column class="items-center gap-2">
            <native:text class="text-[26] font-bold tracking-tight leading-tight text-center text-theme-primary-text">Ennoble</native:text>
            <native:text class="w-full text-[15] leading-relaxed text-center text-theme-secondary-text">
                A private daily practice for a clearer mind.
            </native:text>
        </native:column>
    </native:column>

    <native:column class="rounded-2xl bg-theme-surface shadow-sm">
        <x-native.about-principle-row
            title="Offline by design"
            description="Every exercise, score, and streak lives entirely on this device. Ennoble works with no connection at all."
            :ios="Ios::WifiSlash"
            :android="AndroidOutlined::CloudOff"
        />
        <native:divider />
        <x-native.about-principle-row
            title="Private by default"
            description="No account, no tracking, and nothing to sign into. Your training belongs to you alone."
            :ios="Ios::LockShield"
            :android="AndroidOutlined::VerifiedUser"
        />
        <native:divider />
        <x-native.about-principle-row
            title="Evidence over estimates"
            description="Progress only ever reflects training you actually completed. Nothing is inflated or invented."
            :ios="Ios::CheckmarkSeal"
            :android="AndroidOutlined::FactCheck"
        />
    </native:column>

    <native:column class="items-center gap-1">
        <native:text class="text-[13] font-semibold text-theme-muted-text">{{ $versionLabel }}</native:text>
        <native:text class="text-[13] text-theme-muted-text">Crafted for quiet, focused minds.</native:text>
    </native:column>
</native:column>
</native:scroll-view>
</native:column>
