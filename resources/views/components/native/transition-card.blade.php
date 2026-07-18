@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'previousGame',
    'nextGame',
    'performanceMessage',
    'coaching',
    'coachingDetail',
    'nextPrompt',
    'autoTransitionEnabled',
    'autoTransitionSeconds',
    'isFinalGame' => false,
    'motionDuration' => 0,
])

<native:column
    class="w-80 items-center gap-6 py-5"
    :animate-duration="$motionDuration"
    :a11y-label="$previousGame.' complete. '.$coaching.' '.$performanceMessage.'. Next '.$nextGame.'.'"
>
    <native:stack class="h-44 w-44 items-center justify-center">
        <native:circle :width="176" :height="176" class="bg-theme-success opacity-10" />
        <native:circle :width="120" :height="120" class="border-2 border-theme-success bg-theme-background" />
        <native:column class="h-44 w-44 items-center justify-center">
            <x-native.icon :ios="Ios::Checkmark" :android="AndroidOutlined::Check" :size="42" />
        </native:column>
    </native:stack>

    <native:column class="items-center gap-2">
        <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-success">{{ $previousGame }} complete</native:text>
        <native:text class="text-center text-[34] font-bold tracking-tight leading-tight text-theme-primary-text">{{ $coaching }}</native:text>
        <native:text class="text-center text-[17] leading-relaxed text-theme-secondary-text">{{ $coachingDetail }}</native:text>
    </native:column>

    <native:column class="w-72 items-center gap-2 rounded-2xl bg-theme-secondary-surface shadow-sm p-4">
        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">YOUR MOMENT</native:text>
        <native:text class="text-center text-[17] font-semibold leading-relaxed text-theme-primary-text">
            {{ $performanceMessage }}
        </native:text>
    </native:column>

    <native:column class="items-center gap-2">
        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">{{ $isFinalGame ? 'YOUR WORKOUT' : 'UP NEXT' }}</native:text>
        <native:text class="text-center text-[22] font-semibold tracking-tight text-theme-primary-text">{{ $nextGame }}</native:text>
        <native:text class="text-center text-[15] leading-relaxed text-theme-secondary-text">{{ $nextPrompt }}</native:text>
        <native:text class="text-center text-[13] font-semibold text-theme-muted-text">
            @if ($autoTransitionEnabled)
                Continuing in {{ $autoTransitionSeconds }} {{ $autoTransitionSeconds === 1 ? 'second' : 'seconds' }}
            @else
                Continue when you feel ready
            @endif
        </native:text>
    </native:column>
</native:column>
