@props([
    'motionDuration' => 0,
])

<native:stack class="h-48 w-72 items-center justify-center">
    <native:circle
        :width="120"
        :height="120"
        class="bg-theme-accent opacity-12"
        :scale="1.06"
        :animate-duration="$motionDuration * 3"
        :animate-loop="$motionDuration > 0"
    />
    <native:rect
        :width="176"
        :height="18"
        class="rounded-full bg-theme-secondary-surface"
        :translate-y="-44"
    />
    <native:rect
        :width="208"
        :height="18"
        class="rounded-full bg-theme-accent shadow-lg"
        :animate-duration="$motionDuration * 3"
        :animate-loop="$motionDuration > 0"
        :translate-x="$motionDuration > 0 ? 4 : 0"
    />
    <native:rect
        :width="132"
        :height="18"
        class="rounded-full bg-theme-secondary-surface"
        :translate-y="44"
        :translate-x="-22"
    />
    <native:circle
        :width="18"
        :height="18"
        class="bg-theme-warning shadow-lg"
        :translate-y="44"
        :translate-x="66"
        :animate-duration="$motionDuration * 3"
        :animate-loop="$motionDuration > 0"
    />
</native:stack>
