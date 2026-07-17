@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$shellState">
    <x-slot:empty>
        <x-native.empty-state
            :ios="Ios::PlayCircle"
            :android="AndroidOutlined::PlayCircle"
            title="Your workout is ready"
            description="The guided workout flow is reserved for a later gameplay phase. No session has been started."
        >
            <x-slot:action>
                <native:button
                    label="Back to Home"
                    variant="secondary"
                    @press="goBack"
                />
            </x-slot:action>
        </x-native.empty-state>
    </x-slot:empty>
</x-native.screen-container>
