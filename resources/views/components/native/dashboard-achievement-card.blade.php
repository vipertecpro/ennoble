@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'title' => null,
    'description' => null,
    'motionDuration' => 0,
])

<native:column class="w-80 items-center rounded-3xl border border-theme-border bg-theme-surface-elevated py-5" :animate-duration="$motionDuration">
<native:column class="w-72 gap-4">
    <native:row class="items-center gap-4">
        <native:column class="items-center justify-center rounded-2xl bg-theme-secondary-surface p-3">
            <x-native.icon
                :ios="$title ? Ios::Trophy : Ios::Sparkles"
                :android="$title ? AndroidOutlined::EmojiEvents : AndroidOutlined::AutoAwesome"
                :size="28"
                :a11y-label="$title ? 'Achievement unlocked' : 'Achievement waiting'"
            />
        </native:column>
        <native:column class="flex-1 gap-1">
            @if ($title)
                <native:text class="text-xs font-semibold text-theme-accent">LATEST UNLOCK</native:text>
                <native:text class="text-lg font-semibold text-theme-primary-text">{{ $title }}</native:text>
                <native:text class="text-sm leading-relaxed text-theme-secondary-text">{{ $description }}</native:text>
            @else
                <native:text class="text-lg font-semibold text-theme-primary-text">No achievements yet</native:text>
                <native:text class="text-sm leading-relaxed text-theme-secondary-text">
                    Train consistently and your first milestone will appear here.
                </native:text>
            @endif
        </native:column>
    </native:row>
</native:column>
</native:column>
