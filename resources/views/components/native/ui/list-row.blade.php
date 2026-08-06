@use('App\Icons\Ios')
@use('App\Icons\AndroidOutlined')

{{--
    List row (reference NFT/market style): a gradient icon chip, a title +
    subtitle, and a trailing slot that is either a value, a neon pill, or a
    chevron. Self-contained leaf; the whole row taps through to `method`.
    Stack several inside a `rounded-2xl bg-theme-surface-elevated border` column
    with `<native:divider>` between them.
--}}
@props([
    'ios' => null,
    'android' => null,
    'iconSolid' => null,
    'title' => '',
    'subtitle' => null,
    'trailing' => null,
    'pill' => null,
    'method' => null,
    'chevron' => false,
    'pressScale' => 0.99,
])

<native:pressable
    class="w-full"
    :press-scale="$method ? $pressScale : 1.0"
    a11y-label="{{ $title }}"
    @if ($method) @press="{{ $method }}" @endif
>
    <native:row class="w-full items-center gap-3 px-4 py-3">
        @if ($ios !== null || $android !== null)
            <native:column class="w-10 h-10 items-center justify-center rounded-xl {{ $iconSolid ?? 'bg-theme-surface-variant' }}">
                @if ($iconSolid)
                    <native:icon :ios="$ios" :android="$android" :size="18" class="text-black" />
                @else
                    <x-native.ui.icon :ios="$ios" :android="$android" :size="18" />
                @endif
            </native:column>
        @endif

        <native:column class="flex-1 gap-0.5">
            <native:text class="text-[14] font-semibold text-theme-primary-text">{{ $title }}</native:text>
            @if ($subtitle !== null)
                <native:text class="text-[12] text-theme-muted-text">{{ $subtitle }}</native:text>
            @endif
        </native:column>

        @if ($pill !== null)
            <native:column class="items-center rounded-full bg-theme-accent-cyan/15 border border-theme-accent-cyan/40 px-3 py-1">
                <native:text font="numeric" class="text-[12] text-theme-accent-cyan">{{ $pill }}</native:text>
            </native:column>
        @elseif ($trailing !== null)
            <native:text class="text-[12] text-theme-muted-text">{{ $trailing }}</native:text>
        @elseif ($chevron)
            <x-native.ui.icon :ios="Ios::ChevronForward" :android="AndroidOutlined::ChevronRight" :size="16" a11y-label="Open" />
        @endif
    </native:row>
</native:pressable>
