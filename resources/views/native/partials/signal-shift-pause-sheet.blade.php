@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:bottom-sheet
    :visible="$bottomSheetVisible"
    detents="medium,large"
    ref="signal-shift-pause-sheet"
    @dismiss="resumeWorkout"
>
    <native:column class="gap-5 bg-theme-surface-elevated p-5">
        <native:row class="items-center gap-4">
            <x-native.icon
                :ios="Ios::PauseCircle"
                :android="AndroidOutlined::PauseCircle"
                :size="32"
                a11y-label="Signal Shift paused"
            />
            <native:column class="flex-1 gap-1">
                <native:text class="text-2xl font-bold text-theme-primary-text">Paused</native:text>
                <native:text class="text-sm leading-relaxed text-theme-secondary-text">
                    Your timer and current signal are frozen.
                </native:text>
            </native:column>
        </native:row>

        <native:button label="Resume" size="lg" variant="primary" @press="resumeWorkout" />
        <native:button
            label="Restart"
            size="lg"
            variant="secondary"
            a11y-hint="Clears this unfinished Signal Shift attempt and returns to its instructions"
            @press="restartGame"
        />
        <native:button
            label="Exit"
            size="lg"
            variant="destructive"
            a11y-hint="Keeps this local checkpoint and asks before returning home"
            @press="requestExit"
        />
    </native:column>
</native:bottom-sheet>
