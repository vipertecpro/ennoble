@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="h-full w-full bg-theme-background">
<native:scroll-view class="h-full flex-1 bg-theme-background" :shows-indicators="false">
<native:row class="w-full justify-center bg-theme-background">
<native:column class="w-80 mt-5 mb-12 gap-6">
    <x-native.empty-state
        :ios="Ios::Info"
        :android="AndroidOutlined::Info"
        title="About shell ready"
        description="Ennoble is designed as a fully offline native brain-training application."
    />
</native:column>
</native:row>
</native:scroll-view>
</native:column>
