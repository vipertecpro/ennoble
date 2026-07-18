@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'hasEvidence' => false,
    'workouts' => '0',
    'sessions' => '0',
    'trainingTime' => 'None yet',
    'accuracy' => 'Not measured',
    'response' => 'Not measured',
    'combo' => 'None yet',
    'motionDuration' => 0,
])

<native:column class="w-80 items-center rounded-2xl bg-theme-surface shadow-sm py-5" :animate-duration="$motionDuration">
<native:column class="w-72 gap-3">
    @if (! $hasEvidence)
        <native:row class="items-center gap-4">
            <native:column class="items-center justify-center rounded-xl bg-theme-secondary-surface p-3">
                <x-native.icon
                    :ios="Ios::Hourglass"
                    :android="AndroidOutlined::HourglassEmpty"
                    :size="28"
                    a11y-label="No training evidence yet"
                />
            </native:column>
            <native:column class="flex-1 gap-1">
                <native:text class="text-[17] font-semibold text-theme-primary-text">No training evidence yet</native:text>
                <native:text class="text-[15] leading-relaxed text-theme-secondary-text">
                    Your first completed workout starts this summary. Nothing here is ever estimated.
                </native:text>
            </native:column>
        </native:row>
    @else
        <native:row class="gap-3">
            <x-native.game-stat
                :ios="Ios::CheckmarkSeal"
                :android="AndroidOutlined::Verified"
                label="Workouts"
                :value="$workouts"
            />
            <x-native.game-stat
                :ios="Ios::Gamecontroller"
                :android="AndroidOutlined::SportsEsports"
                label="Sessions"
                :value="$sessions"
            />
        </native:row>

        <native:row class="gap-3">
            <x-native.game-stat
                :ios="Ios::Clock"
                :android="AndroidOutlined::Schedule"
                label="Time trained"
                :value="$trainingTime"
            />
            <x-native.game-stat
                :ios="Ios::Scope"
                :android="AndroidOutlined::TrackChanges"
                label="Accuracy"
                :value="$accuracy"
            />
        </native:row>

        <native:row class="gap-3">
            <x-native.game-stat
                :ios="Ios::Bolt"
                :android="AndroidOutlined::Bolt"
                label="Avg response"
                :value="$response"
            />
            <x-native.game-stat
                :ios="Ios::Flame"
                :android="AndroidOutlined::LocalFireDepartment"
                label="Best combo"
                :value="$combo"
            />
        </native:row>
    @endif
</native:column>
</native:column>
