@props([
    'motionDuration' => 0,
])

<native:stack class="h-56 w-72 items-center justify-center">
    <native:circle
        :width="132"
        :height="132"
        class="bg-theme-accent opacity-12"
        :scale="1.08"
        :animate-duration="$motionDuration * 3"
        :animate-loop="$motionDuration > 0"
    />
    <native:circle
        :width="62"
        :height="62"
        class="bg-theme-accent shadow-lg"
        :translate-x="-58"
        :translate-y="28"
        :animate-duration="$motionDuration * 3"
        :animate-loop="$motionDuration > 0"
    />
    <native:rect
        :width="58"
        :height="58"
        class="bg-theme-warning shadow-lg"
        :rotate="18"
        :translate-x="54"
        :translate-y="-34"
        :animate-duration="$motionDuration * 3"
        :animate-loop="$motionDuration > 0"
    />
    <native:rect
        :width="48"
        :height="48"
        class="bg-theme-danger shadow-lg"
        :rotate="45"
        :translate-x="64"
        :translate-y="56"
        :animate-duration="$motionDuration * 3"
        :animate-loop="$motionDuration > 0"
    />
</native:stack>
