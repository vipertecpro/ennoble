@use('Native\Mobile\Facades\System')

{{--
    Neon circular gauge dial (reference "Speed/Grip" style). Native shapes can't
    draw a partial arc, so the ring is inline SVG in a transparent WKWebView —
    the same offline pattern the water-timer/confetti use. `stroke-dasharray`
    fills the coloured arc to `fraction` (0-1); the big value sits in the centre.
    Colours resolve per appearance so it reads in dark AND light mode.
--}}
@props([
    'value' => '0',
    'fraction' => 1.0,
    'color' => '#C5DB55',
    'label' => null,
])

@php
    $dark = System::appearance() === 'dark';
    $ink = $dark ? '#F5F5F4' : '#1B1B1F';
    $track = $dark ? '#26262C' : '#E7E7E3';

    $f = max(0.0, min(1.0, (float) $fraction));
    $circ = 276.46; // 2*pi*44
    $offset = round($circ * (1 - $f), 2);

    $html = <<<HTML
<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1">
<style>*{margin:0;padding:0}html,body{height:100%;background:transparent;overflow:hidden}svg{display:block;width:100%;height:100%}</style></head>
<body><svg viewBox="0 0 100 100">
<circle cx="50" cy="50" r="44" fill="none" stroke="{$track}" stroke-width="7"/>
<circle cx="50" cy="50" r="44" fill="none" stroke="{$color}" stroke-width="7" stroke-linecap="round" stroke-dasharray="{$circ}" stroke-dashoffset="{$offset}" transform="rotate(-90 50 50)"/>
<text x="50" y="56" text-anchor="middle" font-size="30" font-weight="700" fill="{$ink}" font-family="-apple-system,system-ui,sans-serif">{$value}</text>
</svg></body></html>
HTML;
@endphp

<native:column class="items-center gap-2">
    <native:webview :html="$html" class="w-20 h-20" a11y-label="{{ $label ?? $value }}" />
    @if ($label)
        <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">{{ $label }}</native:text>
    @endif
</native:column>
