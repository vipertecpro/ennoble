@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'title',
    'message',
    'elapsedTime',
    'motionDuration' => 0,
    'actionLabel',
    'actionMethod',
    'actionLoading' => false,
    'actionDisabled' => false,
])

<native:column
    class="w-80 items-center rounded-3xl border border-theme-border bg-theme-surface-elevated py-5"
    :motion-duration="$motionDuration"
    a11y-label="{{ $title }} placeholder game container. Elapsed time {{ $elapsedTime }}."
>
<native:column class="w-72 gap-5">
    <native:row class="items-center gap-4">
        <native:column class="items-center justify-center rounded-2xl bg-theme-primary-surface p-4">
            <x-native.icon
                :ios="Ios::Gamecontroller"
                :android="AndroidOutlined::SportsEsports"
                :size="32"
                a11y-label="Game placeholder"
            />
        </native:column>
        <native:column class="flex-1 gap-1">
            <native:text class="text-xs font-semibold text-theme-accent">FRAMEWORK PLACEHOLDER</native:text>
            <native:text class="text-2xl font-bold leading-tight text-theme-primary-text">{{ $title }}</native:text>
        </native:column>
    </native:row>

    <native:text class="text-base leading-relaxed text-theme-secondary-text">{{ $message }}</native:text>

    <native:column class="gap-1 rounded-2xl bg-theme-secondary-surface p-4">
        <native:text class="text-xs font-semibold text-theme-muted-text">ELAPSED TIME</native:text>
        <native:text class="text-3xl font-bold text-theme-primary-text" a11y-label="Elapsed time {{ $elapsedTime }}">
            {{ $elapsedTime }}
        </native:text>
    </native:column>

    <native:text class="text-sm leading-relaxed text-theme-muted-text">
        Completing this placeholder records only the workout framework checkpoint. No answers, score, accuracy, personal best, or skill progress will be created.
    </native:text>

    <native:button
        class="w-56"
        :label="$actionLabel"
        size="md"
        variant="secondary"
        :loading="$actionLoading"
        :disabled="$actionDisabled"
        a11y-hint="Completes this framework step without creating gameplay evidence"
        @press="{{ $actionMethod }}"
    />
</native:column>
</native:column>
