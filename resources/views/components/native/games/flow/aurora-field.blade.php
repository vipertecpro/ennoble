@props([
    'reducedMotion' => false,
])

{{--
    The Flow aurora field — a living indigo background drawn in a transparent
    WebView, the display-only pattern the water timer established (no bridge, it
    reports nothing back). Several large, heavily-blurred colour blobs drift and
    breathe behind the lane. Every blob is low-alpha over a transparent document,
    so the theme background shows through and the theme-coloured foreground text
    stays readable in both light and dark mode.

    This is a deliberate WebView: a slow, soft, overlapping aurora isn't
    expressible with native EDGE primitives, and it is purely decorative. It is
    keyed once (not per round) so it runs continuously for the whole session.
    Under reduced motion the blobs hold still.
--}}
@php
    $animate = $reducedMotion ? 'none' : 'drift';
    $breathe = $reducedMotion ? 'none' : 'breathe';

    $html = <<<HTML
<!doctype html>
<html><head><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;height:100%;background:transparent;overflow:hidden}
.field{position:fixed;inset:0;overflow:hidden;filter:blur(48px)}
.blob{position:absolute;border-radius:50%;opacity:.6;mix-blend-mode:screen}
.b1{width:70vw;height:70vw;left:-15vw;top:-10vh;background:radial-gradient(circle at center,rgba(99,102,241,0.65),rgba(99,102,241,0) 70%);animation:{$animate} 15s ease-in-out infinite}
.b2{width:60vw;height:60vw;right:-15vw;top:20vh;background:radial-gradient(circle at center,rgba(56,189,248,0.55),rgba(56,189,248,0) 70%);animation:{$animate} 19s ease-in-out infinite reverse}
.b3{width:65vw;height:65vw;left:5vw;bottom:-15vh;background:radial-gradient(circle at center,rgba(45,212,191,0.42),rgba(45,212,191,0) 70%);animation:{$animate} 23s ease-in-out infinite}
.b4{width:45vw;height:45vw;right:5vw;bottom:5vh;background:radial-gradient(circle at center,rgba(129,140,248,0.5),rgba(129,140,248,0) 70%);animation:{$breathe} 11s ease-in-out infinite}
@keyframes drift{0%{transform:translate(0,0) scale(1)}33%{transform:translate(8vw,6vh) scale(1.12)}66%{transform:translate(-6vw,10vh) scale(0.95)}100%{transform:translate(0,0) scale(1)}}
@keyframes breathe{0%{transform:scale(0.9)}50%{transform:scale(1.15)}100%{transform:scale(0.9)}}
</style></head>
<body>
<div class="field">
  <div class="blob b1"></div>
  <div class="blob b2"></div>
  <div class="blob b3"></div>
  <div class="blob b4"></div>
</div>
</body></html>
HTML;
@endphp

<native:column native:key="aurora" class="h-full w-full">
    <native:webview
        :html="$html"
        class="flex-1 w-full"
        a11y-label="Flowing aurora background"
    />
</native:column>
