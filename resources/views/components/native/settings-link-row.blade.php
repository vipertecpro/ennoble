@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'label',
    'description',
    'method',
    'ios',
    'android',
    'pressScale' => 1.0,
    'pressOpacity' => 1.0,
])

<native:pressable
    class="p-4"
    :press-scale="$pressScale"
    :press-opacity="$pressOpacity"
    a11y-label="{{ $label }}"
    a11y-hint="{{ $description }}"
    @press="{{ $method }}"
>
    <native:row class="items-center gap-4">
        <native:column class="items-center justify-center rounded-xl bg-theme-secondary-surface p-3">
            <x-native.icon :ios="$ios" :android="$android" :size="24" />
        </native:column>
        <native:column class="flex-1 gap-1">
            <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $label }}</native:text>
            <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ $description }}</native:text>
        </native:column>
        <x-native.icon
            :ios="Ios::ChevronRight"
            :android="AndroidOutlined::ChevronRight"
            :size="18"
        />
    </native:row>
</native:pressable>
