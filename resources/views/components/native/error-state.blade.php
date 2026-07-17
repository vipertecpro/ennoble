@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'title' => 'Something went wrong',
    'description',
    'showIllustration' => true,
])

<native:column
    class="w-full h-full items-center justify-center"
    :gap="DesignTokens::COMPONENT_SPACING"
    :padding="[DesignTokens::SPACING['4xl'], DesignTokens::SCREEN_PADDING]"
>
    @if ($showIllustration)
        <native:column class="w-20 h-20 items-center justify-center rounded-2xl border border-theme-outline bg-theme-surface">
            <x-native.icon
                :ios="Ios::ExclamationmarkTriangle"
                :android="AndroidOutlined::ErrorOutline"
                :size="DesignTokens::ICON_SIZE['large']"
                a11y-label="Error"
            />
        </native:column>
    @endif
    <native:text class="text-2xl font-semibold text-center text-theme-on-background">{{ $title }}</native:text>
    <native:text class="text-base leading-relaxed text-center text-theme-on-surface-variant">{{ $description }}</native:text>
    @if (isset($retry))
        {{ $retry }}
    @endif
</native:column>
