@props([
    'secondsPerRound' => 7,
    'secondsRemaining' => 7,
    'roundKey' => 0,
])

{{--
    Round-timer water level, drawn in a transparent WebView on both platforms.

    A lime column fills the screen and drains once over the round while two SVG
    wave layers ride its surface. The WebView is transparent on both platforms
    (iOS WKWebView isOpaque=false / Android setBackgroundColor(0)) and both
    engines render the CSS + SVG identically, so a single code path works.

    Sizing never depends on the document's resolved height. The document body
    has zero flow-content height (everything inside is positioned), so any
    percentage/vh height collapses the column to ~0 and only the surface pokes
    out at the top — the "stuck at the top" bug. Instead the water is pinned to
    the viewport with `position:fixed; inset:0` and drained with a `scaleY`
    transform (which needs no resolved height at all), anchored to the bottom so
    it empties downward.

    The WebView is wrapped in a column and sized with `flex-1` (a Compose
    `weight`, i.e. a hard filled height) rather than a bare `h-full`: inside an
    overlay stack, Android's AndroidView won't stretch to `h-full` and instead
    measures to its zero-height content, collapsing the frame to a strip at the
    top. A weighted child gets a definite full height on both platforms.

    Keyed by the round so the level resets to full at each question; the caller
    hides the element the moment an answer locks in, freezing the level exactly
    where the timer stopped.
--}}
@php
    $total = max(1, (int) $secondsPerRound);

    $html = <<<HTML
<!doctype html>
<html><head><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;height:100%;background:transparent;overflow:hidden}
.water{position:fixed;top:0;right:0;bottom:0;left:0;transform-origin:bottom center;
  background:linear-gradient(to top,rgba(197,219,85,0.42),rgba(197,219,85,0.20));
  animation:drain {$total}s linear forwards}
@keyframes drain{0%{transform:scaleY(1)}100%{transform:scaleY(0)}}
.surface{position:absolute;top:-11px;left:0;width:100%;height:20px;overflow:hidden}
.wave{position:absolute;top:0;left:0;width:200%;height:20px;display:flex}
.wave svg{width:50%;height:100%;display:block}
.w1{animation:sway 2.6s linear infinite}
.w2{animation:sway 1.7s linear infinite reverse;opacity:.6}
@keyframes sway{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
</style></head>
<body>
<div class="water">
  <div class="surface">
    <div class="wave w1">
      <svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 10 C15 2 35 2 50 10 C65 18 85 18 100 10 V20 H0 Z" fill="#C5DB55" fill-opacity="0.7"/></svg>
      <svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 10 C15 2 35 2 50 10 C65 18 85 18 100 10 V20 H0 Z" fill="#C5DB55" fill-opacity="0.7"/></svg>
    </div>
    <div class="wave w2">
      <svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 10 C15 18 35 18 50 10 C65 2 85 2 100 10 V20 H0 Z" fill="#C5DB55" fill-opacity="0.9"/></svg>
      <svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 10 C15 18 35 18 50 10 C65 2 85 2 100 10 V20 H0 Z" fill="#C5DB55" fill-opacity="0.9"/></svg>
    </div>
  </div>
</div>
</body></html>
HTML;
@endphp

<native:column native:key="water-{{ $roundKey }}" class="h-full w-full">
    <native:webview
        :html="$html"
        class="flex-1 w-full"
        a11y-label="Round timer water level"
    />
</native:column>
