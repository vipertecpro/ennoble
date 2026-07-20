@props([
    'serial' => 0,
    'reducedMotion' => false,
])

{{--
    A one-shot confetti burst drawn in a transparent WebView — the one native
    EDGE can't express. Purely presentational: it runs its canvas animation once
    on load and reports nothing back (no bridge). The caller mounts it over the
    stage only while a correct answer is on screen and re-keys it per answer, so
    a fresh burst plays each time. Overlaid during the auto-advance beat, when the
    keypad is disabled anyway, so it never eats a tap.
--}}
@php
    $html = <<<'HTML'
    <!doctype html>
    <html><head><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <style>*{margin:0;padding:0}html,body{width:100%;height:100%;background:transparent;overflow:hidden}
    canvas{display:block;width:100%;height:100%}</style></head>
    <body><canvas id="c"></canvas>
    <script>
    (function () {
        var canvas = document.getElementById('c'), ctx = canvas.getContext('2d');
        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        function size() { canvas.width = innerWidth * dpr; canvas.height = innerHeight * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0); }
        size();
        var tints = ['#C5DB55', '#A7C433', '#E4F08A', '#C5DB55'];
        var cx = innerWidth / 2, cy = innerHeight * 0.42, parts = [];
        for (var i = 0; i < 70; i++) {
            var a = Math.random() * Math.PI * 2, s = 3 + Math.random() * 8;
            parts.push({ x: cx, y: cy, vx: Math.cos(a) * s, vy: Math.sin(a) * s - 4,
                sz: 4 + Math.random() * 6, rot: Math.random() * Math.PI, vr: (Math.random() - 0.5) * 0.5,
                color: tints[i % tints.length], life: 0, max: 1 + Math.random() * 0.6 });
        }
        var last = performance.now();
        function frame(now) {
            var dt = Math.min((now - last) / 1000, 0.05); last = now;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            var alive = false;
            for (var i = 0; i < parts.length; i++) {
                var p = parts[i]; p.life += dt; if (p.life >= p.max) continue; alive = true;
                p.vy += dt * 16; p.x += p.vx; p.y += p.vy; p.rot += p.vr;
                ctx.save(); ctx.globalAlpha = Math.max(0, 1 - p.life / p.max);
                ctx.translate(p.x, p.y); ctx.rotate(p.rot); ctx.fillStyle = p.color;
                ctx.fillRect(-p.sz / 2, -p.sz / 2, p.sz, p.sz * 0.6); ctx.restore();
            }
            if (alive) { requestAnimationFrame(frame); }
        }
        requestAnimationFrame(frame);
    })();
    </script></body></html>
    HTML;
@endphp

@unless ($reducedMotion)
    <native:column native:key="confetti-{{ $serial }}" class="w-full h-full">
        <native:webview :html="$html" js="true" class="flex-1 w-full h-full" a11y-label="Correct answer celebration" />
    </native:column>
@endunless
