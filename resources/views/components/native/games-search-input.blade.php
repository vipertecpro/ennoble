@props([
    'searchQuery' => '',
])

<native:outlined-text-input
    ref="games-search"
    native:model.debounce.250ms="searchQuery"
    label="Search games"
    placeholder="Title, category, or description"
    keyboard="text"
    :max-length="60"
    leading-icon="search"
    a11y-label="Search the offline games library"
    a11y-hint="Searches game titles, categories, and descriptions stored on this device"
/>
