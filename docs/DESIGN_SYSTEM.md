# Ennoble Design System

## Direction

Ennoble should feel calm, intelligent, energetic, and premium. The experience favors expressive typography, generous spacing, clear hierarchy, and strong full-screen game moments over dense dashboards.

The visual identity is original. It does not reproduce another brain-training product's logo, illustrations, card layouts, motion, or written tone.

## Implemented Foundation

Prompt 3 establishes the reusable design-system mechanics without attempting final visual polish.

| Foundation | Implemented source |
| --- | --- |
| Semantic appearance | `config/native-ui.php` light and dark palettes |
| Typography, spacing, radii, elevation | `App\NativeUI\Tokens\DesignTokens` |
| Motion, opacity, and icon sizing | `DesignTokens` plus `MotionToken` |
| Theme preference integration | `App\NativeUI\Theme\ThemeManager` |
| Native chrome | `App\NativeLayouts\EnnobleLayout` |
| Shared screen states | `resources/views/components/native/` |
| Typed icons | Generated `App\Icons\Ios`, `Android`, and `AndroidOutlined` enums |

The current semantic palette is intentionally restrained:

| Token group | Light direction | Dark direction |
| --- | --- | --- |
| Primary | Deep violet | Soft violet with dark on-color |
| Background | Warm near-white | Deep neutral |
| Surface | White | Raised deep neutral |
| Accent | Muted warm orange | Soft warm orange |
| Destructive | Deep red | Soft red with dark on-color |

These values are foundation tokens, not final screen art direction. Control visuals remain theme-driven.

## Brand Personality

- **Calm:** uncluttered surfaces and predictable navigation.
- **Intelligent:** precise language, legible information, and meaningful metrics.
- **Energetic:** confident game color, responsive feedback, and restrained celebration.
- **Premium:** consistent spacing, typography, motion, and authored empty states.
- **Supportive:** corrective feedback explains the next useful action without shame.

## Typography

Use one locally bundled, redistribution-safe sans-serif family with regular, medium, semibold, and bold files. The final family requires a separate licensing and visual decision. Do not load fonts at runtime or depend on a CDN.

Suggested roles:

| Role | Intent |
| --- | --- |
| Display | Game title, workout completion, major score |
| Heading | Screen and section titles |
| Body | Instructions, explanations, settings |
| Label | Buttons, tabs, chips, compact metrics |
| Numeric | Scores, time, accuracy, streaks |

Use native dynamic type behavior and verify wrapping at larger accessibility sizes. A bundled font file represents a specific weight; reference the correct file rather than relying on synthesized bold.

## Spacing and Shape

Use a compact scale based on 4-unit increments: 4, 8, 12, 16, 20, 24, 32, and 40. Default screen padding begins at 20 where space permits and reduces deliberately on compact devices.

Use three radius tiers:

- Small for chips, badges, and compact controls.
- Medium for settings rows and secondary cards.
- Large for workout/game cards and result panels.

Avoid making every surface a rounded card. Full-width sections, dividers, spacing, and typography should establish most hierarchy.

## Surface Hierarchy

1. Background: the quiet screen foundation.
2. Surface: grouped content and standard cards.
3. Surface variant: selected, disabled, or secondary regions.
4. Elevated surface: sheets, modals, and temporary foreground content.
5. Game field: game-specific, high-contrast full-screen area.

Theme implementation uses the verified semantic Native UI tokens in `config/native-ui.php`. Per-control colors are not assumed to override theme behavior.

## Color Direction

### Shared Semantics

Define semantic roles rather than screen-specific hex values:

- Primary action.
- Secondary action.
- Background and surface.
- On-background and on-surface text.
- Success, warning, and error.
- Outline and divider.
- Disabled surface and disabled content.
- Focus indicator.

Every state needs a non-color cue such as text, icon, position, or shape.

### Signal Shift

Signal Shift is energetic, geometric, and motion-focused. Explore violet, blue, and electric accents on high-contrast fields. Targets and distractors must remain distinguishable under common color-vision deficiencies.

### Clear Thought

Clear Thought is calm, editorial, and language-focused. Explore warm neutral surfaces with coral, amber, or orange accents. Selected words and sentence segments require clear borders or typography changes in addition to color.

These are design directions, not fixed implementation tokens.

## Icon and Illustration Direction

Use typed native icon enums with appropriate iOS and Android symbols. Do not place emoji in navigation or action labels. Icon-only controls require explicit accessibility labels.

Illustrations use original abstract geometry and simple editorial compositions that can be bundled locally. Avoid detailed decorative art that competes with game instructions. Meaningful images require alt text; decorative images remain silent to screen readers.

## Motion Principles

Motion should explain continuity, confirm input, or celebrate progress. It is not decoration required to understand the interface.

| Moment | Standard behavior | Reduced-motion behavior |
| --- | --- | --- |
| Screen entry | Short native transition preserving direction | Fade or immediate replacement |
| Card press | Subtle scale or elevation response | Static state change |
| Correct answer | Brief emphasis and success confirmation | Instant icon/text confirmation |
| Incorrect answer | Small contained response, never aggressive shaking | Instant error outline and message |
| Combo increase | Numeric emphasis proportional to change | Direct number update |
| Score change | Short count or emphasis | Direct final value |
| Workout completion | Restrained layered celebration | Static completion composition |
| Achievement unlock | Modal/sheet emphasis with one entrance | Static presentation |
| Modal | Native modal presentation | Platform default without extra motion |
| Bottom sheet | Native sheet presentation | Platform default without extra motion |

Before implementation, verify every animation, gesture, and transition property in [NativePHP v4 Gestures and Animation](https://nativephp.com/docs/mobile/4/digging-deeper/gestures-and-animation) and installed source. Unsupported effects must be simplified rather than invented.

Prompt 3 defines only the `fast`, `normal`, `slow`, `spring`, `success`, and `error` timing tokens. Reduced motion resolves these reusable durations to zero through `ThemeManager`; no product or gameplay animation is present.

## Haptic and Sound Principles

- Correct input: light, optional confirmation.
- Incorrect input: distinct but not punitive feedback.
- Achievement or workout completion: a single stronger confirmation.
- Never emit haptics continuously or for decorative animation.
- Honor sound and haptic preferences immediately.
- Pair every sound or haptic cue with visible feedback.

The installed core exposes one generic short vibration. Prompt 3 types future success, error, warning, selection, and impact intents, but intentionally maps enabled feedback to that one verified capability. Distinct native patterns remain unavailable until a later approved and verified implementation.

## Light and Dark Appearance

Light appearance uses quiet warm or neutral backgrounds, dark text, and restrained elevation. Dark appearance uses near-black or deep neutral backgrounds rather than pure black everywhere, with controlled high-chroma game accents.

Both modes must:

- Meet contrast requirements for text and meaningful controls.
- Preserve game-state distinctions.
- Avoid relying on shadows alone.
- Render disabled controls legibly.
- Be tested independently; dark mode is not an automatic color inversion.

## Layout and Safe Areas

- Use layout-managed safe areas for screens under `NativeLayout`.
- Use explicit safe-area utilities only on chrome-less screens after verifying layout behavior.
- Reuse the shared screen container for padding, scrolling, state presentation, and optional chrome-less safe areas.
- Reuse the shared inline top bar only when layout chrome is unsuitable; its left and right action slots must contain accessible controls.
- Support compact phone widths without clipping instructions or controls.
- Allow larger screens to gain breathing room, not uncontrolled line lengths.
- Keep primary gameplay actions within comfortable reach while preserving platform conventions.
- Avoid fixed-height text containers that break under dynamic type.

## Accessibility Rules

- Provide `a11y-label` for icon-only buttons, chips, and tabs.
- Use `a11y-hint` only when it adds information beyond the label.
- Label meaningful images and keep decorative icons silent.
- Make press targets comfortably sized and separated.
- Keep instructions available long enough to read and offer pause/review where timing permits.
- Do not encode correctness, selection, or difficulty through color alone.
- Support VoiceOver and TalkBack reading order.
- Run NativePHP's in-process accessibility audit for every screen, followed by manual assistive-technology verification on each platform.
- Treat reduced motion, sound off, and haptics off as first-class states.

Prompt 3's reusable components use native scalable text, 44-point minimum top-bar targets, explicit icon labels where meaningful, text cues for toast semantics, and in-process accessibility audits. Manual VoiceOver, TalkBack, contrast, large-text, and reading-order evidence still requires platform execution.

## State Checklist

Every implemented screen or reusable component must document and verify:

- Initial.
- Active.
- Loading, when local work can be perceptible.
- Empty.
- Completed.
- Disabled.
- Recoverable error.
- Light appearance.
- Dark appearance.
- Reduced motion.
- Large text.
- Offline operation.

## QA-2 iOS Foundation Decisions

The production UI foundation was exercised on an iPhone 17 Pro simulator running iOS 26.5.

- Onboarding uses `OnboardingLayout`, a native navigation-stack host with its bar hidden. This lets iOS own Dynamic Island and status-bar geometry without exposing application chrome.
- Chrome-less safe-area utilities remain on the inner content column, not the outer stack. Layout-hosted screens do not add a second safe-area inset.
- Scrollable onboarding steps must not use `flex-1`; doing so compresses large Dynamic Type content. Onboarding actions stack at full width so labels remain complete at accessibility sizes.
- Game category chips use two intentional rows of three. Intrinsic chip sizing is preserved without relying on renderer-specific flex wrapping.
- A modal or bottom sheet containing interactive children must not set one group-level `a11y-label`. On iOS that label replaces the children in the accessibility tree. Titles and buttons provide their own semantics.
- System appearance changes repaint the complete semantic light and dark palettes correctly. NativePHP v4 does not currently expose an application-level preferred-color-scheme setter, so Ennoble's explicit Light and Dark preferences cannot yet force SwiftUI/Compose appearance safely. The frozen Native UI mirror was not modified to conceal this upstream gap.

The screenshot evidence for this pass is stored in `docs/screenshots/ios/`.
