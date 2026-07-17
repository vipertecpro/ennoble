@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$shellState">
    <x-slot:empty>
        <x-native.empty-state
            :ios="Ios::Gearshape"
            :android="AndroidOutlined::Settings"
            title="Settings shell ready"
            description="Saved preferences are available to services; controls arrive later."
        >
            <x-slot:action>
                <native:button label="About Ennoble" variant="secondary" @press="openAbout" />
            </x-slot:action>
        </x-native.empty-state>
    </x-slot:empty>
</x-native.screen-container>
