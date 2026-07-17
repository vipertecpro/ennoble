@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$shellState">
    <x-slot:empty>
        <x-native.empty-state
            :ios="Ios::Info"
            :android="AndroidOutlined::Info"
            title="About shell ready"
            description="Ennoble is designed as a fully offline native brain-training application."
        />
    </x-slot:empty>
</x-native.screen-container>
