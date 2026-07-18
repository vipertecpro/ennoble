@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'id',
    'label',
    'shape',
    'colorClass',
    'dimension',
    'rotation',
    'translateX',
    'translateY',
    'moving',
    'direction',
    'resolved',
    'wrong' => false,
    'motionDuration',
    'pressMethod',
    'compact' => false,
])

<native:stack
    native:key="{{ $id }}"
    class="{{ $wrong ? 'border border-theme-danger bg-theme-danger/10' : 'bg-transparent' }} {{ $compact ? 'h-20 w-20' : 'h-28 w-28' }} items-center justify-center rounded-full"
    :opacity="$resolved ? 0.2 : 1"
    :scale="$resolved ? 0.5 : 1"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    :press-scale="$resolved ? 1 : 0.82"
    :press-opacity="$resolved ? 1 : 0.72"
>
    <native:column class="relative {{ $compact ? 'h-20 w-20' : 'h-24 w-24' }} items-center justify-center">
        <native:stack
            class="{{ $compact ? 'h-16 w-16' : 'h-20 w-20' }} items-center justify-center"
            :translate-x="$translateX"
            :translate-y="$translateY"
            :scale="$resolved ? 0.5 : ($moving ? 1.08 : 1.04)"
            :animate-duration="$motionDuration"
            :animate-loop="! $resolved && $motionDuration > 0"
            animate-easing="ease-in-out"
        >
            @if ($shape === 'circle')
                <native:circle
                    :width="$dimension + 14"
                    :height="$dimension + 14"
                    class="{{ $colorClass }} opacity-20"
                />
                <native:circle
                    :width="$dimension"
                    :height="$dimension"
                    class="{{ $colorClass }} shadow-lg"
                />
            @else
                <native:rect
                    :width="$dimension + 14"
                    :height="$dimension + 14"
                    class="{{ $colorClass }} opacity-20"
                    :rotate="$rotation"
                    :border-radius="$shape === 'diamond' ? 10 : 16"
                />
                <native:rect
                    :width="$dimension"
                    :height="$dimension"
                    class="{{ $colorClass }} shadow-lg"
                    :rotate="$rotation"
                    :border-radius="$shape === 'diamond' ? 8 : 16"
                />
            @endif
        </native:stack>

        @if ($moving)
            <native:column
                class="absolute bottom-[0] right-[0] h-7 w-7 items-center justify-center rounded-full bg-theme-surface-elevated"
                a11y-label="Moving {{ $direction }}"
            >
                @if ($direction === 'left')
                    <x-native.icon :ios="Ios::ArrowLeft" :android="AndroidOutlined::ArrowBack" :size="16" />
                @elseif ($direction === 'up')
                    <x-native.icon :ios="Ios::ArrowUp" :android="AndroidOutlined::ArrowUpward" :size="16" />
                @elseif ($direction === 'down')
                    <x-native.icon :ios="Ios::ArrowDown" :android="AndroidOutlined::ArrowDownward" :size="16" />
                @else
                    <x-native.icon :ios="Ios::ArrowRight" :android="AndroidOutlined::ArrowForward" :size="16" />
                @endif
            </native:column>
        @endif
    </native:column>

    <native:button
        ref="{{ $id }}"
        class="absolute h-full w-full"
        label=""
        size="lg"
        variant="ghost"
        a11y-label="{{ $label }}"
        a11y-hint="Tap if this shape matches the current rule"
        :disabled="$resolved"
        @press="{{ $pressMethod }}"
    />
</native:stack>
