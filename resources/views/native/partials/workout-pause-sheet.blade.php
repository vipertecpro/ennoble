@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:bottom-sheet
    :visible="$bottomSheetVisible"
    detents="medium,large"
    @dismiss="dismissBottomSheet"
>
    <native:column class="w-full gap-5 bg-theme-surface p-5">
        <native:row class="w-full items-center gap-4">
            <x-native.icon
                :ios="Ios::PauseCircle"
                :android="AndroidOutlined::PauseCircle"
                :size="32"
                a11y-label="Workout paused"
            />
            <native:column class="flex-1 gap-1">
                <native:text class="text-2xl font-bold text-theme-on-surface">Workout paused</native:text>
                <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
                    Your local checkpoint is safe. Resume, restart this framework workout, or leave and continue later.
                </native:text>
            </native:column>
        </native:row>

        <native:button label="Resume" size="lg" variant="primary" @press="resumeWorkout" />
        <native:button
            label="Restart Workout"
            size="lg"
            variant="secondary"
            a11y-hint="Clears only framework placeholder sessions and returns to the introduction"
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
</native:bottom-sheet>
