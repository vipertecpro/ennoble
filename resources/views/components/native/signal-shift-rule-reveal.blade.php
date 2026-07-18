@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'rule',
    'round',
    'motionDuration' => 0,
])

<native:stack
    native:key="rule-{{ $round }}"
    class="h-64 w-72 items-center justify-center"
    :scale="1.06"
    :animate-duration="$motionDuration * 2"
    animate-easing="ease-out"
>
    <native:circle :width="248" :height="248" class="bg-theme-accent opacity-10" />
    <native:circle :width="204" :height="204" class="border border-theme-accent bg-theme-primary-surface" />
    <native:column class="w-44 items-center gap-3">
        <x-native.icon :ios="Ios::Scope" :android="AndroidOutlined::CenterFocusStrong" :size="34" />
        <native:text class="text-center text-2xl font-bold leading-tight text-theme-primary-text">{{ $rule }}</native:text>
    </native:column>
</native:stack>
