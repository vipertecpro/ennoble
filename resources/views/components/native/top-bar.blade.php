@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'title',
    'subtitle' => null,
    'showBack' => false,
])

<native:row class="min-h-[56] items-center gap-3 bg-theme-background px-5 py-3">
    @if ($showBack)
        <native:pressable
            :width="DesignTokens::MINIMUM_TOUCH_TARGET"
            :height="DesignTokens::MINIMUM_TOUCH_TARGET"
            class="items-center justify-center rounded-full"
            a11y-label="Go back"
            @press="goBack"
        >
            <x-native.icon
                :ios="Ios::ChevronLeft"
                :android="AndroidOutlined::ArrowBack"
                :size="DesignTokens::ICON_SIZE['medium']"
            />
        </native:pressable>
    @elseif (isset($leftAction))
        {{ $leftAction }}
    @else
        <native:spacer :width="DesignTokens::MINIMUM_TOUCH_TARGET" />
    @endif

    <native:column class="flex-1 gap-1">
        <native:text class="text-lg font-semibold text-theme-primary-text">{{ $title }}</native:text>
        @if ($subtitle)
            <native:text class="text-sm text-theme-secondary-text">{{ $subtitle }}</native:text>
        @endif
    </native:column>

    @if (isset($rightAction))
        {{ $rightAction }}
    @else
        <native:spacer :width="DesignTokens::MINIMUM_TOUCH_TARGET" />
    @endif
</native:row>
