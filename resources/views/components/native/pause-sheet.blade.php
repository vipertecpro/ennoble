@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="gap-5 bg-theme-surface-elevated p-5">
    <native:row class="items-center gap-4">
        <x-native.icon
            :ios="Ios::PauseCircle"
            :android="AndroidOutlined::PauseCircle"
            :size="32"
            a11y-label="Workout paused"
        />
        <native:column class="flex-1 gap-1">
            <native:text class="text-2xl font-bold text-theme-primary-text">Workout paused</native:text>
            <native:text class="text-sm leading-relaxed text-theme-secondary-text">
                Your local checkpoint is safe. Resume, restart this placeholder step, or leave and continue later.
            </native:text>
        </native:column>
    </native:row>

    <native:button label="Resume" size="lg" variant="primary" @press="resumeWorkout" />
    <native:button
        label="Restart Current Step"
        size="lg"
        variant="secondary"
        a11y-hint="Clears only this framework placeholder step"
        @press="restartWorkout"
    />
    <native:button
        label="Exit Workout"
        size="lg"
        variant="destructive"
        a11y-hint="Opens a confirmation before returning home"
        @press="requestExit"
    />
</native:column>
