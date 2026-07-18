@use('App\Enums\ThemePreference')
@use('App\NativeUI\Theme\ThemeManager')

@props([
    'ios',
    'android',
    'size' => 24,
    'a11yLabel' => null,
    'color' => null,
    'darkColor' => null,
])

@php
    $theme = app(ThemeManager::class);
    $preference = $theme->currentPreference();
    $resolvedColor = $color ?? $theme->color('primary-text', $preference, ThemePreference::Light->value);
    $resolvedDarkColor = $darkColor ?? (
        $preference === ThemePreference::System
            ? $theme->color('primary-text', $preference, ThemePreference::Dark->value)
            : $resolvedColor
    );
@endphp

<native:icon
    :ios="$ios"
    :android="$android"
    :size="$size"
    :color="$resolvedColor"
    :dark-color="$resolvedDarkColor"
    :a11y-label="$a11yLabel"
/>
