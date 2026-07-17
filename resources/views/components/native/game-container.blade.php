@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'title',
    'message',
    'elapsedTime',
    'motionDuration' => 0,
])

<native:column
    class="w-full gap-5 rounded-3xl bg-theme-primary p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $title }} placeholder game container. Elapsed time {{ $elapsedTime }}."
>
    <native:row class="w-full items-center gap-4">
        <native:column class="items-center justify-center rounded-2xl bg-theme-secondary p-4">
            <x-native.icon
                :ios="Ios::Gamecontroller"
                :android="AndroidOutlined::SportsEsports"
                :size="32"
                a11y-label="Game placeholder"
            />
        </native:column>
        <native:column class="flex-1 gap-1">
            <native:text class="text-xs font-semibold text-theme-on-primary">FRAMEWORK PLACEHOLDER</native:text>
            <native:text class="text-2xl font-bold leading-tight text-theme-on-primary">{{ $title }}</native:text>
        </native:column>
    </native:row>

    <native:text class="text-base leading-relaxed text-theme-on-primary">{{ $message }}</native:text>

    <native:column class="w-full gap-1 rounded-2xl bg-theme-secondary p-4">
        <native:text class="text-xs font-semibold text-theme-on-secondary">ELAPSED TIME</native:text>
        <native:text class="text-3xl font-bold text-theme-on-secondary" a11y-label="Elapsed time {{ $elapsedTime }}">
            {{ $elapsedTime }}
        </native:text>
    </native:column>

    <native:text class="text-sm leading-relaxed text-theme-on-primary">
        Completing this placeholder records only the workout framework checkpoint. No answers, score, accuracy, personal best, or skill progress will be created.
    </native:text>

    {{ $slot }}
</native:column>
