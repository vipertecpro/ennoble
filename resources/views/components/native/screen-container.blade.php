@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'state' => 'content',
    'scroll' => false,
    'safeArea' => false,
])

<native:stack class="w-full h-full bg-theme-background {{ $safeArea ? 'safe-area' : '' }}">
    @if ($state === 'loading')
        {{ $loading ?? '' }}
    @elseif ($state === 'empty')
        {{ $empty ?? $slot }}
    @elseif ($state === 'error')
        {{ $error ?? '' }}
    @elseif ($scroll)
        <native:scroll-view class="w-full h-full">
            <native:column
                class="w-full"
                :gap="DesignTokens::COMPONENT_SPACING"
                :padding="[DesignTokens::SPACING['2xl'], DesignTokens::SCREEN_PADDING]"
            >
                {{ $slot }}
            </native:column>
        </native:scroll-view>
    @else
        <native:column
            class="w-full h-full"
            :gap="DesignTokens::COMPONENT_SPACING"
            :padding="[DesignTokens::SPACING['2xl'], DesignTokens::SCREEN_PADDING]"
        >
            {{ $slot }}
        </native:column>
    @endif

    {{ $overlays ?? '' }}
</native:stack>
