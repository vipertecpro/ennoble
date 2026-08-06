@use('App\NativeUI\Tokens\Gradients')

{{--
    Compact stat tile (Badges "Your stats"): an icon + uppercase label over a
    bold value, on a clean surface card with a hairline border. No coloured
    gradient or heavy shadow — it reads calmly in both light and dark and stays
    consistent with the other content cards.
--}}
@props([
    'ios',
    'android',
    'label',
    'value',
])

<native:column class="flex-1 gap-2 rounded-2xl bg-theme-surface border {{ Gradients::hairline() }} p-4">
    <native:row class="items-center gap-2">
        <x-native.ui.icon :ios="$ios" :android="$android" :size="18" />
        <native:text class="text-[11] font-semibold uppercase tracking-wider text-theme-secondary-text">{{ $label }}</native:text>
    </native:row>
    <native:text class="text-[17] font-bold leading-tight text-theme-primary-text">{{ $value }}</native:text>
</native:column>
