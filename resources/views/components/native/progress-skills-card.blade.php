@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'skills' => [],
    'motionDuration' => 0,
])

<native:column class="w-80 items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
<native:column class="w-72 gap-4">
    @if (count($skills) === 0)
        <native:row class="items-center gap-4">
            <native:column class="items-center justify-center rounded-xl bg-theme-secondary-surface p-3">
                <x-native.icon
                    :ios="Ios::ChartBar"
                    :android="AndroidOutlined::TrendingUp"
                    :size="28"
                    a11y-label="No skill progress yet"
                />
            </native:column>
            <native:column class="flex-1 gap-1">
                <native:text class="text-[17] font-semibold text-theme-primary-text">No skill progress yet</native:text>
                <native:text class="text-[15] leading-relaxed text-theme-secondary-text">
                    Completed gameplay builds an evidence-backed profile of every trained skill.
                </native:text>
            </native:column>
        </native:row>
    @else
        @foreach ($skills as $skill)
            <native:column class="gap-2">
                <native:row class="items-center justify-between">
                    <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $skill['label'] }}</native:text>
                    <native:row class="items-center gap-2">
                        <native:text class="text-[13] font-semibold {{ $skill['delta'] > 0 ? 'text-theme-success' : ($skill['delta'] < 0 ? 'text-theme-danger' : 'text-theme-muted-text') }}">
                            {{ $skill['deltaLabel'] }}
                        </native:text>
                        <native:text class="text-[15] text-theme-muted-text">{{ $skill['score'] }} / 1000</native:text>
                    </native:row>
                </native:row>
                <native:progress-bar
                    :value="$skill['progress']"
                    a11y-label="{{ $skill['label'] }} skill score {{ $skill['score'] }} out of 1000, recent change {{ $skill['deltaLabel'] }}"
                />
            </native:column>

            @unless ($loop->last)
                <native:divider />
            @endunless
        @endforeach
    @endif
</native:column>
</native:column>
