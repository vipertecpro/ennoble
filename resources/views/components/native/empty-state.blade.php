@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'ios',
    'android',
    'title',
    'description',
])

<native:column
    class="w-full h-full items-center justify-center"
    :gap="DesignTokens::COMPONENT_SPACING"
    :padding="[DesignTokens::SPACING['4xl'], DesignTokens::SCREEN_PADDING]"
>
    <native:column class="w-16 h-16 items-center justify-center rounded-full bg-theme-surface-variant">
        <x-native.icon
            :ios="$ios"
            :android="$android"
            :size="DesignTokens::ICON_SIZE['large']"
            :a11y-label="$title"
        />
    </native:column>
    <native:text class="text-2xl font-semibold text-center text-theme-on-background">{{ $title }}</native:text>
    <native:text class="text-base leading-relaxed text-center text-theme-on-surface-variant">{{ $description }}</native:text>
    @if (isset($action))
        {{ $action }}
    @endif
</native:column>
