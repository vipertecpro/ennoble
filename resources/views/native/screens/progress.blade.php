@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<x-native.screen-container :state="$shellState">
    <x-slot:empty>
        <x-native.empty-state
            :ios="Ios::ChartBar"
            :android="AndroidOutlined::TrendingUp"
            title="Progress shell ready"
            description="Statistics and progress UI remain intentionally unavailable."
        />
    </x-slot:empty>
</x-native.screen-container>
