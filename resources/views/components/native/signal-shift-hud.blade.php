@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'rule',
    'timer',
    'score',
    'lives',
    'maxLives',
    'combo',
    'progress',
    'gameRound' => 1,
    'wave' => 1,
    'waveCount' => 1,
    'showPause' => false,
    'motionDuration' => 0,
])

<native:column class="w-full gap-3">
    <native:row class="h-12 w-full items-center justify-between gap-3">
        <native:row
            class="items-center gap-2"
        >
            @for ($life = 1; $life <= $maxLives; $life++)
                <native:circle
                    native:key="signal-life-{{ $life }}"
                    :width="12"
                    :height="12"
                    class="{{ $life <= $lives ? 'bg-theme-danger' : 'border border-theme-danger bg-theme-background opacity-40' }}"
                    :scale="$life <= $lives ? 1 : 0.72"
                    :animate-duration="$motionDuration"
                    animate-easing="ease-out"
                />
            @endfor
            <native:badge
                :count="$lives"
                variant="destructive"
                a11y-label="{{ $lives }} of {{ $maxLives }} lives remaining"
            />
        </native:row>

        <native:column class="items-center gap-0">
            <native:text
                class="text-[22] font-semibold tracking-tight text-theme-primary-text"
                a11y-label="{{ $timer }} seconds remaining"
            >
                {{ $timer }}
            </native:text>
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SECONDS</native:text>
        </native:column>

        <native:column class="items-end gap-0">
            <native:text class="text-[15] font-semibold text-theme-primary-text">{{ number_format($score) }}</native:text>
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SCORE</native:text>
        </native:column>

        @if ($showPause)
            <native:stack
                class="h-12 w-12 items-center justify-center rounded-full bg-theme-secondary-surface"
                :press-scale="0.88"
                :press-opacity="0.72"
            >
                <x-native.icon :ios="Ios::Pause" :android="AndroidOutlined::Pause" :size="22" />
                <native:button
                    ref="Pause Signal Shift"
                    class="absolute h-full w-full"
                    label=""
                    size="md"
                    variant="ghost"
                    a11y-label="Pause Signal Shift"
                    a11y-hint="Pauses the timer and opens game options"
                    @press="pauseWorkout"
                />
            </native:stack>
        @endif
    </native:row>

    <native:progress-bar
        :progress="$progress"
        a11y-label="Signal Shift {{ (int) round($progress * 100) }} percent complete"
    />

    <native:row class="w-full items-center justify-between gap-3">
        <native:column class="flex-1 gap-1">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">
                ROUND {{ $gameRound }} · WAVE {{ $wave }} OF {{ $waveCount }}
            </native:text>
            <native:text class="text-[17] font-semibold leading-tight text-theme-primary-text">{{ $rule }}</native:text>
        </native:column>

        @if ($combo > 1)
            <native:row
                native:key="combo-{{ $combo }}"
                class="items-center gap-1"
                :scale="1.08"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
                a11y-label="Combo {{ $combo }}"
            >
                <x-native.icon :ios="Ios::FlameFill" :android="AndroidOutlined::LocalFireDepartment" :size="20" />
                <native:text class="text-[22] font-semibold tracking-tight text-theme-warning">x{{ $combo }}</native:text>
            </native:row>
        @endif
    </native:row>
</native:column>
