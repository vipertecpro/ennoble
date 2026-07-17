@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$shellState">
    <x-slot:empty>
        <x-native.empty-state
            :ios="Ios::Gamecontroller"
            :android="AndroidOutlined::SportsEsports"
            title="Games shell ready"
            description="No gameplay is implemented in this application-shell prompt."
        />
    </x-slot:empty>
</x-native.screen-container>
