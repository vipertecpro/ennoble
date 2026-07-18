@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'current' => 0,
    'longest' => 0,
    'motionDuration' => 0,
])

<native:column class="w-80 items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
<native:column class="w-72 gap-4">
    @if ($current === 0)
        <native:row class="items-center gap-4">
            <native:column class="items-center justify-center rounded-xl bg-theme-secondary-surface p-3">
                <x-native.icon
                    :ios="Ios::Flame"
                    :android="AndroidOutlined::LocalFireDepartment"
                    :size="28"
                    a11y-label="No active streak"
                />
            </native:column>
            <native:column class="flex-1 gap-1">
                <native:text class="text-[17] font-semibold text-theme-primary-text">No streak yet</native:text>
                <native:text class="text-[15] leading-relaxed text-theme-secondary-text">
                    Complete both activities in a daily workout to begin.
                </native:text>
            </native:column>
        </native:row>
    @else
        <native:row class="items-end gap-4">
            <native:column class="flex-1 gap-1">
                <native:text class="text-[34] font-bold tracking-tight text-theme-primary-text">{{ $current }}</native:text>
                <native:text class="text-[15] font-semibold text-theme-muted-text">
                    {{ $current === 1 ? 'day in rhythm' : 'days in rhythm' }}
                </native:text>
            </native:column>
            <native:column class="items-end gap-1">
                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">LONGEST</native:text>
                <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">{{ $longest }} days</native:text>
            </native:column>
        </native:row>

        <native:text class="text-[15] leading-relaxed text-theme-secondary-text">
            Consistency is building quietly. Keep the rhythm useful, not perfect.
        </native:text>
    @endif

    <native:row
        class="items-center gap-2"
        a11y-label="{{ min($current, 7) }} of 7 streak markers active"
    >
        @for ($day = 1; $day <= 7; $day++)
            <native:column
                class="flex-1 h-2 rounded-full {{ $day <= min($current, 7) ? 'bg-theme-accent' : 'bg-theme-divider' }}"
            />
        @endfor
    </native:row>
</native:column>
</native:column>
