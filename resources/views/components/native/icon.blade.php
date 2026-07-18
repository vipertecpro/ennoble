@use('Nativephp\NativeUi\Theme')

{{--
    Icon colors must follow the LOADED theme tokens, not the OS appearance.
    When the player forces Light or Dark in-app, ThemeManager loads that
    palette into both the light and dark token slots — reading both slots
    here means icons repaint with the rest of the UI. Under the System
    preference the slots differ and the renderer picks by OS color scheme.
--}}

@props([
    'ios',
    'android',
    'size' => 24,
    'a11yLabel' => null,
    'color' => null,
    'darkColor' => null,
])

@php
    $tokens = Theme::all();
    $resolvedColor = $color
        ?? data_get($tokens, 'light.primary-text')
        ?? config('native-ui.theme.light.primary-text', '#1B1B1F');
    $resolvedDarkColor = $darkColor
        ?? data_get($tokens, 'dark.primary-text')
        ?? config('native-ui.theme.dark.primary-text', '#F5F5F4');
@endphp

<native:icon
    :ios="$ios"
    :android="$android"
    :size="$size"
    :color="$resolvedColor"
    :dark-color="$resolvedDarkColor"
    :a11y-label="$a11yLabel"
/>
