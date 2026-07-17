@props([
    'category',
    'label',
    'selected' => false,
])

<native:chip
    ref="filter-{{ $category }}"
    :label="$label"
    :selected="$selected"
    a11y-label="Filter games by {{ $label }}"
    a11y-hint="{{ $selected ? 'Currently selected' : 'Shows matching games' }}"
    @change="setCategory('{{ $category }}')"
/>
