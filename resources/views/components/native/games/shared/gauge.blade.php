@use('Native\Mobile\Facades\System')

{{--
    Neon circular gauge dial (reference "Speed/Grip" style). Native shapes can't
    draw a partial arc, so the ring is inline SVG in a transparent WKWebView.
    `stroke-dasharray` fills the coloured arc to `fraction` (0-1).

    The centre value is ALWAYS centred — `text-anchor=middle` +
    `dominant-baseline=central` at (50,50) — and its font-size scales DOWN as the
    string grows, so a four/five/six-digit count (e.g. a 12,345 best score) can
    never overflow the dial or break the layout.
--}}
@props([
    'value' => '0',
    'fraction' => 1.0,
    'color' => '#C5DB55',
    'label' => null,
    'size' => 'w-20 h-20',
])

@php
    $dark = System::appearance() === 'dark';
    $ink = $dark ? '#F4F6F8' : '#1B1B1F';
    $track = $dark ? '#3C3C41' : '#E7E7E3';

    $f = max(0.0, min(1.0, (float) $fraction));
    $circ = 276.46; // 2 * pi * 44
    $offset = round($circ * (1 - $f), 2);

    $val = (string) $value;
    $len = mb_strlen($val);
    // Scale the centre value so long counts stay inside the ring.
    $fs = match (true) {
        $len <= 3 => 30,
        $len === 4 => 24,
        $len === 5 => 20,
        default => 16,
    };

    $html = <<<HTML
<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1">
<style>*{margin:0;padding:0}html,body{height:100%;background:transparent;overflow:hidden}svg{display:block;width:100%;height:100%}</style></head>
<body><svg viewBox="0 0 100 100">
<circle cx="50" cy="50" r="44" fill="none" stroke="{$track}" stroke-width="7"/>
<circle cx="50" cy="50" r="44" fill="none" stroke="{$color}" stroke-width="7" stroke-linecap="round" stroke-dasharray="{$circ}" stroke-dashoffset="{$offset}" transform="rotate(-90 50 50)"/>
<text x="50" y="50" text-anchor="middle" dominant-baseline="central" font-size="{$fs}" font-weight="700" fill="{$ink}" font-family="-apple-system,system-ui,sans-serif">{$val}</text>
</svg></body></html>
HTML;
@endphp

<native:column class="items-center gap-2">
    <native:webview :html="$html" class="{{ $size }}" a11y-label="{{ $label ?? $val }}" />
    @if ($label)
        <native:text class="text-[10] font-semibold uppercase tracking-widest text-theme-muted-text">{{ $label }}</native:text>
    @endif
</native:column>
