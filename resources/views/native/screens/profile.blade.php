@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1" :shows-indicators="false">
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
    {{-- Identity hero — vibrant lime → cyan gradient with a glowing border --}}
    <native:column class="w-full items-center rounded-3xl bg-linear-to-br from-lime-400/30 via-cyan-400/10 to-transparent border border-lime-400/40 shadow-lg py-7" :animate-duration="$motionDuration">
    <native:column class="w-full px-4 items-center gap-4">
        <native:column class="w-20 h-20 items-center justify-center rounded-full bg-theme-surface-elevated border-2 border-lime-400/50 shadow-lg">
            @if ($monogram !== '')
                <native:text font="headline" class="text-[24] text-theme-primary-text">{{ $monogram }}</native:text>
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
            <native:text font="headline" class="text-[19] tracking-tight leading-tight text-center text-theme-primary-text">{{ $identityName }}</native:text>
            <native:text class="text-[13] text-theme-muted-text">{{ $memberSince }}</native:text>
        </native:column>

        <native:column class="items-center rounded-full bg-lime-400/15 border border-lime-400/30 px-4 py-1">
            <native:text class="text-[13] font-semibold text-theme-secondary-text">
                {{ $goalLabel }} · {{ $paceLabel }}
            </native:text>
        </native:column>
    </native:column>
    </native:column>

    {{-- Level / XP --}}
    <native:column class="w-full rounded-2xl bg-theme-surface-elevated border border-theme-border shadow-md px-4 py-4 gap-2.5">
        <native:row class="w-full items-center">
            <native:text class="flex-1 text-[13.5] font-semibold text-theme-primary-text">Level {{ $level }} · {{ $levelTitle }}</native:text>
            <native:text font="numeric" class="text-[12.5] text-theme-secondary-text">{{ $xpLabel }}</native:text>
        </native:row>
        <x-native.ui.progress :value="$levelProgress" token="accent" />
    </native:column>

    {{-- Navigation — a single grouped card of list rows --}}
    <native:column class="w-full rounded-2xl bg-theme-surface-elevated border border-theme-border overflow-hidden">
        <x-native.ui.list-row
            :ios="Ios::PersonTextRectangle"
            :android="AndroidOutlined::Badge"
            iconSolid="bg-linear-to-br from-lime-400 to-cyan-400"
            title="My Details"
            subtitle="Your name, focus, and pace"
            chevron
            method="openMyDetails"
        />
        <native:divider />
        <x-native.ui.list-row
            :ios="Ios::Gearshape"
            :android="AndroidOutlined::Settings"
            iconSolid="bg-linear-to-br from-cyan-400 to-sky-500"
            title="Settings"
            subtitle="Appearance, feedback, and motion"
            chevron
            method="openSettings"
        />
        <native:divider />
        <x-native.ui.list-row
            :ios="Ios::Info"
            :android="AndroidOutlined::Info"
            iconSolid="bg-linear-to-br from-amber-400 to-orange-500"
            title="About Ennoble"
            subtitle="A private offline games companion"
            chevron
            method="openAbout"
        />
    </native:column>
    @endif
</native:column>
</native:scroll-view>
</native:column>
