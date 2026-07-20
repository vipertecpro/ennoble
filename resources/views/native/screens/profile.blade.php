@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:column class="w-full px-4 mt-5 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.ui.loading-overlay label="Loading your profile" />
    @elseif ($screenState === 'error')
        <x-native.ui.error-state
            :description="$screenError"
            retry-label="Retry profile"
            retry-method="retryProfile"
        />
    @else
    <native:column class="w-full items-center rounded-2xl bg-theme-primary-surface py-6" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 items-center gap-4">
        <native:column class="w-20 h-20 items-center justify-center rounded-full bg-theme-surface-elevated shadow-sm">
            @if ($monogram !== '')
                <native:text class="text-[22] font-bold tracking-tight text-theme-primary-text">{{ $monogram }}</native:text>
            @else
                <x-native.ui.icon
                    :ios="Ios::Person"
                    :android="AndroidOutlined::Person"
                    :size="32"
                    a11y-label="Local profile"
                />
            @endif
        </native:column>

        <native:column class="items-center gap-1">
            <native:text class="text-[18] font-semibold tracking-tight leading-tight text-center text-theme-primary-text">{{ $identityName }}</native:text>
            <native:text class="text-[13] text-theme-muted-text">{{ $memberSince }}</native:text>
        </native:column>

        <native:text class="text-[13] font-semibold text-theme-secondary-text">
            {{ $goalLabel }} · {{ $paceLabel }}
        </native:text>
    </native:column>
    </native:column>

    <native:column class="rounded-2xl bg-theme-surface shadow-sm">
        <x-native.settings.link-row
            label="My Details"
            description="Your name, focus, and pace."
            method="openMyDetails"
            :ios="Ios::PersonTextRectangle"
            :android="AndroidOutlined::Badge"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
        />
        <native:divider />
        <x-native.settings.link-row
            label="Settings"
            description="Appearance, feedback, and motion."
            method="openSettings"
            :ios="Ios::Gearshape"
            :android="AndroidOutlined::Settings"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
        />
        <native:divider />
        <x-native.settings.link-row
            label="About Ennoble"
            description="A private offline games companion."
            method="openAbout"
            :ios="Ios::Info"
            :android="AndroidOutlined::Info"
            :press-scale="$pressScale"
            :press-opacity="$pressOpacity"
        />
    </native:column>
    @endif
</native:column>
</native:scroll-view>
</native:column>
