@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'ios',
    'android',
    'title',
    'description',
    'actionLabel' => null,
    'actionMethod' => null,
])

<native:column
    class="h-full items-center justify-center"
    :gap="DesignTokens::COMPONENT_SPACING"
    :padding="[DesignTokens::SPACING['2xl'], DesignTokens::SCREEN_PADDING]"
>
    <native:column class="w-16 h-16 items-center justify-center rounded-2xl bg-theme-primary-surface">
        <x-native.icon
            :ios="$ios"
            :android="$android"
            :size="DesignTokens::ICON_SIZE['large']"
            :a11y-label="$title"
        />
    </native:column>
    <native:text class="text-[22] font-semibold tracking-tight text-center text-theme-primary-text">{{ $title }}</native:text>
    <native:text class="text-[17] leading-relaxed text-center text-theme-secondary-text">{{ $description }}</native:text>
    @if ($actionLabel && $actionMethod)
        <native:button
            class="w-44"
            :label="$actionLabel"
            size="md"
            variant="secondary"
            @press="{{ $actionMethod }}"
        />
    @endif
</native:column>
