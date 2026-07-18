@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'stimuli',
    'resolvedStimulusIds',
    'feedbackTone' => 'neutral',
    'feedbackMessage' => '',
    'feedbackSerial' => 0,
    'floatingScore' => '',
    'combo' => 0,
    'motionDuration' => 0,
    'feedbackMotionDuration' => 0,
    'tutorial' => false,
])

<native:stack
    class="{{ $tutorial ? 'h-[320] w-full' : 'w-full flex-1' }} items-center justify-center"
    :translate-x="! $tutorial && $feedbackTone === 'danger' ? ($feedbackSerial % 2 === 0 ? 7 : -7) : 0"
    :animate-duration="! $tutorial && $feedbackTone === 'danger' ? $motionDuration : 0"
    animate-easing="ease-out"
>
    <native:circle :width="$tutorial ? 290 : 304" :height="$tutorial ? 290 : 304" class="border border-theme-divider opacity-30" />
    <native:circle :width="$tutorial ? 206 : 220" :height="$tutorial ? 206 : 220" class="border border-theme-divider opacity-20" />
    <native:circle :width="$tutorial ? 122 : 136" :height="$tutorial ? 122 : 136" class="bg-theme-primary-surface opacity-40" />

    <native:column class="w-full items-center justify-center gap-1">
        @foreach (array_chunk($stimuli, count($stimuli) > 4 ? 3 : 4) as $stimulusRow)
            <native:row class="w-full items-center justify-center {{ $tutorial || count($stimuli) === 4 ? 'gap-0' : 'gap-1' }}">
                @foreach ($stimulusRow as $stimulus)
                    <x-native.signal-shift-stimulus
                        :id="$stimulus['id']"
                        :label="$stimulus['label']"
                        :shape="$stimulus['shape']"
                        :color-class="$stimulus['color_class']"
                        :dimension="$stimulus['dimension']"
                        :rotation="$stimulus['rotation']"
                        :translate-x="$stimulus['translate_x']"
                        :translate-y="$stimulus['translate_y']"
                        :moving="$stimulus['moving']"
                        :direction="$stimulus['direction']"
                        :resolved="in_array($stimulus['id'], $resolvedStimulusIds, true)"
                        :wrong="$feedbackTone === 'danger' && in_array($stimulus['id'], $resolvedStimulusIds, true)"
                        :motion-duration="$stimulus['motion_duration']"
                        :press-method="$stimulus['press_method']"
                        :compact="$tutorial || count($stimuli) === 4"
                    />
                @endforeach
            </native:row>
        @endforeach
    </native:column>

    @if (! $tutorial && $feedbackTone === 'success')
        <native:stack
            native:key="success-burst-{{ $feedbackSerial }}"
            class="h-32 w-32 items-center justify-center"
        >
            @foreach ([[-34, -28], [0, -44], [36, -20], [-38, 24], [4, 42], [40, 26]] as [$particleX, $particleY])
                <native:circle
                    :width="9"
                    :height="9"
                    class="bg-theme-success"
                    :translate-x="$particleX"
                    :translate-y="$particleY"
                    :scale="0.2"
                    :opacity="0"
                    :animate-duration="$feedbackMotionDuration"
                    animate-easing="ease-out"
                />
            @endforeach
            <native:column class="items-center gap-1">
                <native:text class="text-4xl font-bold text-theme-success">{{ $floatingScore }}</native:text>
                @if ($combo > 1)
                    <native:row class="items-center gap-1">
                        <x-native.icon :ios="Ios::FlameFill" :android="AndroidOutlined::LocalFireDepartment" :size="20" />
                        <native:text class="text-xl font-bold text-theme-warning">x{{ $combo }}</native:text>
                    </native:row>
                @endif
            </native:column>
        </native:stack>
    @elseif (! $tutorial && $feedbackTone === 'danger')
        <native:column
            native:key="wrong-feedback-{{ $feedbackSerial }}"
            class="h-24 w-24 items-center justify-center rounded-full bg-theme-danger/10"
            :scale="1.18"
            :opacity="0.2"
            :animate-duration="$feedbackMotionDuration"
            animate-easing="ease-out"
            a11y-label="{{ $feedbackMessage }}"
        >
            <x-native.icon :ios="Ios::Xmark" :android="AndroidOutlined::Close" :size="42" />
        </native:column>
    @endif
</native:stack>
