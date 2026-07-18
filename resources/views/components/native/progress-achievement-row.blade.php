@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'name',
    'description',
    'detail',
    'unlocked' => false,
])

<native:row class="items-start gap-4 p-5">
    <native:column class="items-center justify-center rounded-xl {{ $unlocked ? 'bg-theme-primary-surface' : 'bg-theme-secondary-surface' }} p-3">
        <x-native.icon
            :ios="$unlocked ? Ios::Rosette : Ios::Lock"
            :android="$unlocked ? AndroidOutlined::MilitaryTech : AndroidOutlined::Lock"
            :size="24"
            :a11y-label="$unlocked ? 'Unlocked achievement' : 'Locked achievement'"
        />
    </native:column>
    <native:column class="flex-1 gap-1">
        <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $name }}</native:text>
        <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ $description }}</native:text>
        <native:text class="text-[13] font-semibold {{ $unlocked ? 'text-theme-accent' : 'text-theme-muted-text' }}">
            {{ $detail }}
        </native:text>
    </native:column>
</native:row>
