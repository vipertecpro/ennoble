@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'motionDuration' => 0,
])

<native:carousel
    :item-width="272"
    :item-spacing="14"
    variant="uncontained"
    a11y-label="Why Ennoble training areas"
>
    <x-native.onboarding-feature-card
        :ios="Ios::Scope"
        :android="AndroidOutlined::CenterFocusStrong"
        title="Focus"
        description="Practice staying with the signal while distractions fall away."
        :motion-duration="$motionDuration"
    />
    <x-native.onboarding-feature-card
        :ios="Ios::Bolt"
        :android="AndroidOutlined::Bolt"
        title="Processing Speed"
        description="Build a calm, accurate response to information that changes quickly."
        :motion-duration="$motionDuration"
    />
    <x-native.onboarding-feature-card
        :ios="Ios::TextBubble"
        :android="AndroidOutlined::TextFields"
        title="Language"
        description="Shape clearer sentences and communicate ideas with precision."
        :motion-duration="$motionDuration"
    />
    <x-native.onboarding-feature-card
        :ios="Ios::ChartLineUptrendXyaxis"
        :android="AndroidOutlined::TrendingUp"
        title="Daily Growth"
        description="Let small, consistent sessions compound into lasting progress."
        :motion-duration="$motionDuration"
    />
</native:carousel>
