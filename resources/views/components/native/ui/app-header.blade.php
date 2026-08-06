@use('App\Icons\Ios')
@use('App\Icons\Android')
@use('App\Icons\AndroidOutlined')
@use('App\NativeUI\Tokens\Gradients')

{{--
    Primary-screen header — the consistent top strip across Home / Games /
    Achievements / Profile. Left: an optional eyebrow + a headline-font title.
    Right: a streak pill, a level pill, and a settings icon-button (the
    brain-training analogue of the references' coin / gem / level chips).

    Flat row with a flex-1 title column so the pills and button size to their
    content without triggering the iOS nested-centred-row collapse.
--}}
@props([
    'eyebrow' => null,
    'title' => '',
    'titleLine2' => null,
    'streak' => null,
    'level' => null,
    'settings' => true,
    'settingsMethod' => 'openSettings',
])

<native:row class="w-full items-center gap-2.5">
    <native:column class="flex-1">
        @if ($eyebrow)
            <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">{{ $eyebrow }}</native:text>
        @endif
        <native:text font="headline" class="text-[22] tracking-tight leading-tight text-theme-primary-text">{{ $title }}</native:text>
        @if ($titleLine2)
            <native:text font="headline" class="text-[22] tracking-tight leading-tight text-theme-primary-text">{{ $titleLine2 }}</native:text>
        @endif
    </native:column>

    @if ($streak !== null)
        <native:row class="items-center gap-1.5 rounded-full bg-theme-surface-elevated border {{ Gradients::hairline() }} px-3 py-2" a11y-label="Streak {{ $streak }} days">
            <native:icon :ios="Ios::FlameFill" :android="Android::LocalFireDepartment" :size="14" class="text-theme-accent-amber" />
            <native:text font="numeric" class="text-[13] text-theme-primary-text">{{ $streak }}</native:text>
        </native:row>
    @endif

    @if ($level !== null)
        <native:row class="items-center gap-1.5 rounded-full bg-theme-surface-elevated border {{ Gradients::hairline() }} px-3 py-2" a11y-label="Level {{ $level }}">
            <native:icon :ios="Ios::StarFill" :android="Android::Star" :size="13" class="text-theme-accent" />
            <native:text font="numeric" class="text-[13] text-theme-primary-text">Lv {{ $level }}</native:text>
        </native:row>
    @endif

    @if ($settings)
        <x-native.ui.icon-button
            :ios="Ios::Gearshape"
            :android="AndroidOutlined::Settings"
            :method="$settingsMethod"
            a11y-label="Settings"
        />
    @endif
</native:row>
