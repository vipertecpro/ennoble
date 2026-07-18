@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'state' => 'content',
    'scroll' => false,
    'safeArea' => false,
])

<native:stack
    native:key="screen-container-{{ $scroll ? 'scroll' : 'fixed' }}-{{ $safeArea ? 'safe' : 'standard' }}"
    class="w-full h-full bg-theme-background"
>
    @if ($scroll)
        <native:scroll-view native:key="screen-content-scroll" class="w-full h-full">
            <native:column
                class="w-full {{ $safeArea ? 'safe-area' : '' }}"
                :gap="DesignTokens::COMPONENT_SPACING"
                :padding="[DesignTokens::SPACING['2xl'], DesignTokens::SCREEN_PADDING]"
            >
                {{ $slot->isEmpty() ? ($empty ?? '') : $slot }}
            </native:column>
        </native:scroll-view>
    @else
        <native:column
            native:key="screen-content-fixed"
            class="w-full h-full {{ $safeArea ? 'safe-area' : '' }}"
            :gap="DesignTokens::COMPONENT_SPACING"
            :padding="[DesignTokens::SPACING['2xl'], DesignTokens::SCREEN_PADDING]"
        >
            {{ $slot->isEmpty() ? ($empty ?? '') : $slot }}
        </native:column>
    @endif

    {{ $overlays ?? '' }}
</native:stack>
