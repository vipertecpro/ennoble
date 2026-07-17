@props([
    'ios',
    'android',
    'size' => 24,
    'a11yLabel' => null,
])

<native:icon
    :ios="$ios"
    :android="$android"
    :size="$size"
    :a11y-label="$a11yLabel"
/>
