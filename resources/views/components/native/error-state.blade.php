@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'title' => 'Something went wrong',
    'description',
    'showIllustration' => true,
    'retryLabel' => null,
    'retryMethod' => null,
])

<native:column
    class="h-full items-center justify-center"
    :gap="DesignTokens::COMPONENT_SPACING"
    :padding="[DesignTokens::SPACING['4xl'], DesignTokens::SCREEN_PADDING]"
>
    @if ($showIllustration)
        <native:column class="w-20 h-20 items-center justify-center rounded-3xl border border-theme-border bg-theme-secondary-surface">
            <x-native.icon
                :ios="Ios::ExclamationmarkTriangle"
                :android="AndroidOutlined::ErrorOutline"
                :size="DesignTokens::ICON_SIZE['large']"
                a11y-label="Error"
            />
        </native:column>
    @endif
    <native:text class="text-2xl font-semibold text-center text-theme-primary-text">{{ $title }}</native:text>
    <native:text class="text-base leading-relaxed text-center text-theme-secondary-text">{{ $description }}</native:text>
    @if ($retryLabel && $retryMethod)
        <native:button
            class="w-44"
            :label="$retryLabel"
            size="md"
            variant="secondary"
            @press="{{ $retryMethod }}"
        />
    @endif
</native:column>
