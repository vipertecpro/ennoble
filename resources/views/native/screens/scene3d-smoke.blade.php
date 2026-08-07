{{--
    Development smoke test for the scene3d plugin. Delete with the screen.

    The viewport sits in a fixed-height box rather than filling the screen so
    that the surrounding EDGE chrome stays visible: if the 3D view blows out
    its bounds or swallows the layout, that is itself a finding.
--}}
<native:column class="w-full h-full gap-4 p-4 safe-area">
    <native:text class="text-[20] font-bold text-theme-primary-text">Scene3D smoke test</native:text>

    <native:text class="text-[13] leading-relaxed text-theme-secondary-text">
        The box is metal, so it can only show what the environment gives it — black means there is no indirect light. The sphere is emissive and lights itself.
    </native:text>

    <native:scene-3d class="w-full h-[320px] rounded-2xl" :scene="$scene" />

    <native:button variant="primary" @tap="toggleSphere">
        {{ $showSphere ? 'Remove sphere' : 'Add sphere' }}
    </native:button>

    <native:text class="text-[12] leading-relaxed text-theme-muted-text">
        Adding the sphere must not restart the box's spin — if it stutters, the renderer is rebuilding instead of diffing.
    </native:text>
</native:column>
