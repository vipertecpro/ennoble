@props([
    'secondsPerRound' => 7,
])

{{--
    Real animated water, rendered offline inside a transparent WKWebView via
    inline HTML (no plugin, no native build, no network — hot-reloads). A
    lime-tinted body drains over the round while two SVG wave layers sway on
    offset periods for a live surface. Sits behind the gameplay as the back
    layer of a native:stack.
--}}
@php
    $seconds = max(1, (int) $secondsPerRound);
    $html = <<<HTML
<!doctype html>
<html><head><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;background:transparent;overflow:hidden}
.water{position:absolute;left:0;right:0;bottom:0;height:100%;
  background:linear-gradient(to top,rgba(197,219,85,0.22),rgba(197,219,85,0.09));
  animation:drain {$seconds}s linear infinite}
@keyframes drain{0%{height:100%}100%{height:0%}}
.surface{position:absolute;top:-13px;left:0;width:100%;height:20px;overflow:hidden}
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
      <svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 10 C15 2 35 2 50 10 C65 18 85 18 100 10 V20 H0 Z" fill="#C5DB55" fill-opacity="0.55"/></svg>
      <svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 10 C15 2 35 2 50 10 C65 18 85 18 100 10 V20 H0 Z" fill="#C5DB55" fill-opacity="0.55"/></svg>
    </div>
    <div class="wave w2">
      <svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 10 C15 18 35 18 50 10 C65 2 85 2 100 10 V20 H0 Z" fill="#C5DB55" fill-opacity="0.75"/></svg>
      <svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 10 C15 18 35 18 50 10 C65 2 85 2 100 10 V20 H0 Z" fill="#C5DB55" fill-opacity="0.75"/></svg>
    </div>
  </div>
</div>
</body></html>
HTML;
@endphp

<native:webview
    :html="$html"
    class="h-full w-full"
    a11y-label="Round timer water level"
/>
