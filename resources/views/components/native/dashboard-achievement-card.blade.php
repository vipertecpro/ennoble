@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'title' => null,
    'description' => null,
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-4 rounded-3xl bg-theme-surface p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
>
    <native:row class="w-full items-center gap-4">
        <native:column class="items-center justify-center rounded-2xl bg-theme-surface-variant p-3">
            <x-native.icon
                :ios="$title ? Ios::Trophy : Ios::Sparkles"
                :android="$title ? AndroidOutlined::EmojiEvents : AndroidOutlined::AutoAwesome"
                :size="28"
                :a11y-label="$title ? 'Achievement unlocked' : 'Achievement waiting'"
            />
        </native:column>
        <native:column class="flex-1 gap-1">
            @if ($title)
                <native:text class="text-xs font-semibold text-theme-primary">LATEST UNLOCK</native:text>
                <native:text class="text-lg font-semibold text-theme-on-surface">{{ $title }}</native:text>
                <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">{{ $description }}</native:text>
            @else
                <native:text class="text-lg font-semibold text-theme-on-surface">No achievements yet</native:text>
                <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
                    Train consistently and your first milestone will appear here.
                </native:text>
            @endif
        </native:column>
    </native:row>
</native:column>
