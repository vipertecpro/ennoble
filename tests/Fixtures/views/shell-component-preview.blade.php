@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="w-full h-full">
    <x-native.top-bar title="Reusable top bar" subtitle="Both action slots are available">
        <x-slot:leftAction>
            <native:pressable
                class="w-11 h-11 items-center justify-center"
                a11y-label="Left action"
                @press="noop"
            >
                <x-native.icon
                    :ios="Ios::Lightbulb"
                    :android="AndroidOutlined::Lightbulb"
                    :size="24"
                />
            </native:pressable>
        </x-slot:leftAction>
        <x-slot:rightAction>
            <native:pressable
                class="w-11 h-11 items-center justify-center"
                a11y-label="Right action"
                @press="noop"
            >
                <x-native.icon
                    :ios="Ios::Info"
                    :android="AndroidOutlined::Info"
                    :size="24"
                />
            </native:pressable>
        </x-slot:rightAction>
    </x-native.top-bar>

    <x-native.loading-overlay mode="inline" label="Loading inline" />
    <x-native.loading-overlay mode="button" label="Loading action" />
</native:column>
