@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$shellState">
    <x-slot:empty>
        <x-native.empty-state
            :ios="Ios::Person"
            :android="AndroidOutlined::Person"
            title="Profile shell ready"
            description="Profile functionality remains outside this prompt."
        >
            <x-slot:action>
                <native:button label="Open settings" variant="secondary" @press="openSettings" />
            </x-slot:action>
        </x-native.empty-state>
    </x-slot:empty>
</x-native.screen-container>
