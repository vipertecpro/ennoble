@props([
    'secondsPerRound' => 7,
    'secondsRemaining' => 7,
    'roundKey' => 0,
])

{{--
    Round-timer water level, rendered the best way each platform allows.

    iOS keeps the original transparent-WKWebView water: a lime body that
    drains once over the round while two SVG wave layers sway for a live
    surface. It works flawlessly there, so it stays exactly as-is.

    Android's locked-down WebView won't reliably run the CSS keyframes, so it
    draws a fully native fallback instead — a ladder of equal-weight cells,
    tinted lime up to the current level, stepping down one band per second.
    No WebView, so it draws identically under Compose.

    Both are keyed by the round so the level resets to full at each question;
    the caller hides the element the moment an answer locks in, freezing the
    level exactly where the timer stopped.
--}}
@php
    $total = max(1, (int) $secondsPerRound);
    $remaining = max(0, min($total, (int) $secondsRemaining));
@endphp

@if (\Native\Mobile\Facades\System::isIos())
    @php
        $html = <<<HTML
<!doctype html>
<html><head><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;background:transparent;overflow:hidden}
.water{position:absolute;left:0;right:0;bottom:0;height:100%;
  background:linear-gradient(to top,rgba(197,219,85,0.22),rgba(197,219,85,0.09));
  animation:drain {$total}s linear forwards}
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
        native:key="water-{{ $roundKey }}"
        :html="$html"
        class="h-full w-full"
        a11y-label="Round timer water level"
    />
@else
    @php
        $cells = 24;
        $filled = (int) round($remaining / $total * $cells);
        $surfaceIndex = $cells - $filled;
    @endphp

    <native:column native:key="water-{{ $roundKey }}" class="h-full w-full">
        @for ($i = 0; $i < $cells; $i++)
            <native:column class="flex-1 w-full {{ $i < $surfaceIndex ? 'bg-transparent' : ($i === $surfaceIndex ? 'bg-[#C5DB55]/40' : 'bg-[#C5DB55]/20') }}" />
        @endfor
    </native:column>
@endif
