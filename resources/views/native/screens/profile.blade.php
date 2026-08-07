@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')

<native:column class="h-full w-full {{ Gradients::screen() }}">
<native:scroll-view class="h-full flex-1" :shows-indicators="false">
<native:column class="w-full px-4 ios:safe-area-top mt-2 ios:mt-0 mb-12 gap-6">
    @if ($screenState === 'loading')
        <x-native.ui.loading-overlay label="Loading your profile" />
    @elseif ($screenState === 'error')
        <x-native.ui.error-state
            :description="$screenError"
            retry-label="Retry profile"
            retry-method="retryProfile"
        />
    @else
    {{-- Identity card — the profile's own header: avatar + name/meta on the left,
         a settings gear on the right, goal + pace as hairline pills below. No
         separate "Profile" title line — the tab already names the screen. --}}
    <native:column class="w-full rounded-3xl bg-linear-to-br from-rose-500/26 via-rose-500/15 to-transparent border {{ Gradients::hairline() }} p-4 gap-3.5" :animate-duration="$motionDuration">
        <native:row class="w-full items-center gap-3.5">
            <native:column class="w-16 h-16 items-center justify-center rounded-2xl bg-theme-surface-elevated border-2 border-rose-500/50 shadow-md">
                @if ($monogram !== '')
                    <native:text font="headline" class="text-[22] text-theme-primary-text">{{ $monogram }}</native:text>
                @else
                    <x-native.ui.icon
                        :ios="Ios::Person"
                        :android="AndroidOutlined::Person"
                        :size="28"
                        a11y-label="Local profile"
                    />
                @endif
            </native:column>

            <native:column class="flex-1 gap-0.5">
                <native:text font="headline" class="text-[20] tracking-tight leading-tight text-theme-primary-text">{{ $identityName }}</native:text>
                <native:text class="text-[13] text-theme-muted-text">{{ $memberSince }}</native:text>
            </native:column>

            <x-native.ui.icon-button
                :ios="Ios::Gearshape"
                :android="AndroidOutlined::Settings"
                method="openSettings"
                a11y-label="Settings"
            />
        </native:row>

        <native:row class="items-center gap-2">
            <native:row class="items-center gap-1.5 rounded-full bg-theme-surface-elevated border {{ Gradients::hairline() }} px-3 py-1.5">
                <native:icon :ios="Ios::Target" :android="AndroidOutlined::GpsFixed" :size="13" class="text-theme-accent" />
                <native:text class="text-[13] font-semibold text-theme-secondary-text">{{ $goalLabel }}</native:text>
            </native:row>
            <native:row class="items-center gap-1.5 rounded-full bg-theme-surface-elevated border {{ Gradients::hairline() }} px-3 py-1.5">
                <native:icon :ios="Ios::Bolt" :android="AndroidOutlined::Bolt" :size="13" class="text-theme-accent-cyan" />
                <native:text class="text-[13] font-semibold text-theme-secondary-text">{{ $paceLabel }}</native:text>
            </native:row>
        </native:row>
    </native:column>

    {{-- Level / XP --}}
    <native:column class="w-full rounded-2xl bg-theme-surface-elevated border {{ Gradients::hairline() }} shadow-md px-4 py-4 gap-2.5">
        <native:row class="w-full items-center">
            <native:text class="flex-1 text-[13.5] font-semibold text-theme-primary-text">Level {{ $level }} · {{ $levelTitle }}</native:text>
            <native:text font="numeric" class="text-[12.5] text-theme-secondary-text">{{ $xpLabel }}</native:text>
        </native:row>
        <x-native.ui.progress :value="$levelProgress" token="accent" />
    </native:column>

    {{-- Lifetime stats --}}
    <x-native.dashboard.section-header title="Lifetime stats" />
    <native:row class="w-full gap-2.5">
        <x-native.ui.stat-chip :value="$accuracyLabel" label="Accuracy" token="accent-cyan" :ios="Ios::Target" :android="AndroidOutlined::GpsFixed" />
        <x-native.ui.stat-chip :value="$speedLabel" label="Speed" token="accent-violet" :ios="Ios::Bolt" :android="AndroidOutlined::Bolt" />
        <x-native.ui.stat-chip :value="$bestLabel" label="Best" token="accent-amber" :ios="Ios::Crown" :android="AndroidOutlined::WorkspacePremium" />
        <x-native.ui.stat-chip :value="$gamesLabel" label="Games" token="accent" :ios="Ios::Gamecontroller" :android="AndroidOutlined::SportsEsports" />
    </native:row>

    {{-- Navigation — a single grouped card of list rows --}}
    <native:column class="w-full rounded-2xl bg-theme-surface-elevated border {{ Gradients::hairline() }} overflow-hidden">
        <x-native.ui.list-row
            :ios="Ios::PersonTextRectangle"
            :android="AndroidOutlined::Badge"
            iconSolid="bg-linear-to-br from-rose-500 to-orange-400"
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
