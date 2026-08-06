# Ennoble "game-premium" redesign — shared UI kit

Four reusable EDGE components under `resources/views/components/native/ui/`.
Compose screens from these instead of re-authoring gradients/borders inline.
Reference quality: the "At a glance" badges in
`resources/views/native/screens/home.blade.php`.

---

## HARD RULES (apply to every screen you restyle)

- **Native EDGE only**: Blade `<native:*>` tags + Tailwind utility classes.
  NEVER inline `style=""`. No web views.
- **Both modes**: must look good in dark AND light. Base background is true
  black (dark) / white (light); surfaces `#17171A` / `#F5F5F2`.
  DO NOT edit `config/native-ui.php` background/surface tokens.
- **Base colours via theme tokens** (`bg-theme-*`, `text-theme-*`,
  `border-theme-*`) so both modes adapt. Vibrant identity/gradient colours may
  use palette names (`lime-400`, `cyan-500`, `orange-500`, `red-600`,
  `amber-400`) or hex, applied consistently.
- **Gradients**: `bg-linear-to-{t,b,r,br,…}` with `from-/via-/to-`. Opacity
  modifiers OK (`from-lime-400/30`). Gradient stops bake ONE value (not
  dark/light aware) — for anything that must differ per mode, compute the stops
  in PHP from `System::appearance()` (see `screen.blade.php`).
- **iOS FLEX GOTCHA**: never bury visible content inside a `justify-center` /
  `justify-between` NESTED `<native:row>` — on iOS the nested row collapses to
  zero width and its content vanishes. Put content directly in a
  `<native:column>`, or use a flat row with `<native:spacer class="flex-1"/>`
  between two sides.
- **Safe-area**: do NOT add/remove safe-area classes.
- **Preserve everything**: keep all text, labels, `@press`/`@tap` handlers,
  `native:model` bindings, and `native:key` values. RESTYLE ONLY — tests assert
  visible text and structure.
- **Per-item identity accents**: streak = orange→red (fire); games/accuracy =
  lime→cyan; speed = amber; nitro/danger = red. Re-brand Recall to the
  lime/teal theme (away from off-brand cyan/purple).

---

## 1. `<x-native.ui.screen>` — mode-aware gradient background

Wraps a screen's content in a subtle, appearance-aware vertical gradient
(near-black→faint dark tint→black in dark; white→faint tint→white in light).
Because gradient stops bake a single value, it reads `System::appearance()` in
PHP and picks the stops for the current mode. Subtle depth, not a rainbow.

**Props**

| prop  | type   | default | notes                          |
|-------|--------|---------|--------------------------------|
| class | string | `''`    | extra classes merged onto root |

```blade
<x-native.ui.screen class="safe-area-top">
    <native:scroll-view class="h-full flex-1" :shows-indicators="false">
        {{-- screen content --}}
    </native:scroll-view>
</x-native.ui.screen>
```

---

## 2. `<x-native.ui.gradient-button>` — glossy primary CTA

A bright `lime-400 → cyan-400` gradient pressable: full-round, bold dark centred
label, shadow, press-scale. Handler passed by method name.

**Props**

| prop       | type   | default | notes                          |
|------------|--------|---------|--------------------------------|
| label      | string | —       | button text (required)         |
| press      | string | `null`  | `@press` method name           |
| pressScale | float  | `0.96`  | press feedback scale           |
| class      | string | `''`    | extra classes (e.g. width)     |

```blade
<x-native.ui.gradient-button label="Start game" press="startGame" />
```

---

## 3. `<x-native.ui.glow-card>` — gradient card wrapper

Subtle theme-surface gradient fill (`surface → surface-variant`, both modes
adapt), `rounded-3xl`, `shadow-lg`. Optional `accent` adds a glowing coloured
border for identity.

**Props**

| prop   | type   | default | notes                                       |
|--------|--------|---------|---------------------------------------------|
| accent | string | `null`  | palette colour for glow border, e.g. `lime-400`; omit for a neutral `theme-border` |
| class  | string | `p-4`   | padding/layout (override to change rhythm)  |

```blade
<x-native.ui.glow-card accent="orange-500" class="p-5 gap-3">
    <native:text class="text-[17] font-semibold text-theme-primary-text">Day streak</native:text>
    {{-- … --}}
</x-native.ui.glow-card>
```

---

## 4. `<x-native.ui.stat-badge>` — vibrant gradient stat

The extracted "At a glance" badge: icon/lottie slot, big number, coloured
uppercase label, two-tone gradient fill + glowing border. Colour driven by
`accent` (with optional two-tone + label overrides).

**Props**

| prop       | type   | default        | notes                                        |
|------------|--------|----------------|----------------------------------------------|
| value      | string | —              | big number (required)                        |
| label      | string | —              | uppercase caption (required)                 |
| accent     | string | `lime-400`     | identity hue: gradient first stop + border   |
| accentTo   | string | = `accent`     | gradient second stop                         |
| labelColor | string | = `accent`     | uppercase label colour                       |
| `icon` slot| slot   | —              | optional top icon/lottie (sized `w-14 h-14`) |

```blade
<x-native.ui.stat-badge :value="$currentStreak" label="Day streak"
    accent="orange-500" accentTo="red-600" labelColor="orange-400">
    <x-slot:icon>
        <native:lottie-player source="flame" loop class="flex-1 w-full" alt="Streak flame" />
    </x-slot:icon>
</x-native.ui.stat-badge>
```

Place two badges in a flat `<native:row class="gap-3">` (each is `flex-1`).
