@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'current' => 0,
    'longest' => 0,
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-4 rounded-3xl bg-theme-surface p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    @if ($current === 0)
        <native:row class="w-full items-center gap-4">
            <native:column class="items-center justify-center rounded-2xl bg-theme-surface-variant p-3">
                <x-native.icon
                    :ios="Ios::Flame"
                    :android="AndroidOutlined::LocalFireDepartment"
                    :size="28"
                    a11y-label="No active streak"
                />
            </native:column>
            <native:column class="flex-1 gap-1">
                <native:text class="text-lg font-semibold text-theme-on-surface">No streak yet</native:text>
                <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
                    Complete both activities in a daily workout to begin.
                </native:text>
            </native:column>
        </native:row>
    @else
        <native:row class="w-full items-end gap-4">
            <native:column class="flex-1 gap-1">
                <native:text class="text-4xl font-bold text-theme-on-surface">{{ $current }}</native:text>
                <native:text class="text-sm font-semibold text-theme-on-surface-variant">
                    {{ $current === 1 ? 'day in rhythm' : 'days in rhythm' }}
                </native:text>
            </native:column>
            <native:column class="items-end gap-1">
                <native:text class="text-xs font-semibold text-theme-on-surface-variant">LONGEST</native:text>
                <native:text class="text-xl font-bold text-theme-on-surface">{{ $longest }} days</native:text>
            </native:column>
        </native:row>

        <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
            Consistency is building quietly. Keep the rhythm useful, not perfect.
        </native:text>
    @endif

    <native:row
        class="w-full items-center gap-2"
        a11y-label="{{ min($current, 7) }} of 7 streak markers active"
    >
        @for ($day = 1; $day <= 7; $day++)
            <native:column
                class="flex-1 h-2 rounded-full {{ $day <= min($current, 7) ? 'bg-theme-accent' : 'bg-theme-surface-variant' }}"
            />
        @endfor
    </native:row>
</native:column>
