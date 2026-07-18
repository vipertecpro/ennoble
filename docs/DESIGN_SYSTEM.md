# Ennoble Design System

## Product Direction

Ennoble is a calm, private daily practice for cognitive fitness. Its interface should feel minimal, focused, confident, elegant, and intelligent. It should never resemble a corporate dashboard, a component demonstration, or a collection of unrelated cards.

The reference work supplied for Prompt Design-2 informed three principles without being copied:

1. Give each screen one dominant idea.
2. Prefer short copy, generous white space, and precise alignment.
3. Use one restrained action instead of oversized full-width controls.

Ennoble’s illustrations, copy, geometry, colors, and compositions remain original.

## Visual Principles

- **Quiet foundation:** backgrounds recede so typography and the current task lead.
- **One focal point:** every screen has one visual anchor, one headline, and one obvious next action.
- **Bounded reading width:** primary phone content is 320 points wide and centered.
- **Editorial hierarchy:** section titles and spacing separate content before borders do.
- **Honest states:** unavailable gameplay, statistics, and progress remain explicitly unavailable.
- **Native behavior:** controls, navigation, sheets, dialogs, scaling, and accessibility remain platform-native.

## Theme Architecture

`config/native-ui.php` is the color source of truth. Application views use semantic `theme-*` utilities; component-specific hex values are prohibited.

The 19 application roles are:

| Role | Purpose |
| --- | --- |
| `background` | Full-screen foundation |
| `surface` | Standard grouped content |
| `surface-elevated` | Foreground cards and temporary surfaces |
| `primary-surface` | Quiet brand-tinted hero areas |
| `secondary-surface` | Supporting neutral areas |
| `primary-text` | Headings and essential labels |
| `secondary-text` | Body copy |
| `muted-text` | Metadata and tertiary copy |
| `divider` | Internal separation |
| `border` | Restrained card definition |
| `accent` | Primary brand emphasis |
| `success` | Confirmed positive state |
| `warning` | Caution requiring attention |
| `danger` | Error or destructive state |
| `overlay` | Dialog and sheet scrim |
| `pressed` | Press feedback |
| `selected` | Selected control or row |
| `disabled` | Unavailable controls |
| `focus-ring` | Keyboard and accessibility focus |

Native control tokens (`primary`, `secondary`, `surface`, `background`, their `on-*` pairs, outline, destructive, and accent) map to the same visual philosophy.

### Light Theme

Light mode uses a warm near-white background, white elevated surfaces, dark neutral type, pale mineral-grey support surfaces, and a restrained deep-teal accent. Hierarchy comes from space and tone; borders are reserved for cards that need a visible boundary.

### Dark Theme

Dark mode uses a near-black charcoal background, quiet zinc surfaces, high-contrast neutral type, and a soft teal accent. It avoids blue or purple foundations, tinted card collections, and high-chroma decoration. Cards separate through small tonal changes and subtle borders.

### Theme Switching

`ThemeManager` applies the selected semantic palette and clears the Native UI Tailwind parser cache so rerenders do not retain stale semantic values. System appearance follows the platform. Explicit Light and Dark choices use the matching semantic tokens; platform chrome behavior must continue to be verified against the installed NativePHP implementation.

## Typography

Ennoble uses the native system family so iOS and Android retain familiar rendering and Dynamic Type behavior.

| Role | Token | Intended use |
| --- | --- | --- |
| Display XL | `text-5xl font-bold leading-tight` | Rare completion or score moment |
| Display Large | `text-4xl font-bold leading-tight` | Onboarding statement |
| Headline | `text-3xl font-bold leading-tight` | Screen heading |
| Title | `text-2xl font-semibold leading-tight` | Card or major section title |
| Section | `text-xl font-semibold leading-tight` | Section heading |
| Body | `text-base leading-relaxed` | Primary explanatory copy |
| Body Small | `text-sm leading-relaxed` | Supporting copy and metadata |
| Caption | `text-xs font-semibold` | Eyebrows and compact labels |
| Button | `text-base font-semibold` | Action labels |
| Badge | `text-xs font-semibold` | Status and category labels |
| Numeric | `text-3xl font-bold leading-tight` | Time, scores, and metrics |

Rules:

- Keep onboarding titles to one short thought.
- Keep body blocks within the 288-point inner reading width.
- Use sentence case for actions and headings.
- Use uppercase only for short eyebrows.
- Do not reduce text below the caption role.
- Avoid fixed-height text containers.

## Spacing System

The base scale is 4, 8, 12, 16, 20, 24, 32, 40, and 48 points.

| Layout role | Value |
| --- | ---: |
| Screen margin | 20 pt |
| Main content width | 320 pt |
| Inner card width | 288 pt |
| Section gap | 24 pt |
| Card inset | 20 pt |
| Standard content gap | 16 pt |
| Compact gap | 12 pt |
| Minimum touch target | 44 pt |

Primary screens use a centered 320-point column. Cards use a 288-point inner column, producing consistent 16-point side insets. Larger devices gain surrounding space without allowing uncontrolled line length.

## Layout Families

### Onboarding

- Chrome-free native stack.
- Compact progress and dots at the top.
- One dominant original geometric illustration.
- One short heading, one short support line, and one action row.
- Back is a quiet ghost action; Continue is a restrained 176-point action.

### Dashboard

- The greeting is the header; a redundant system title bar is not shown.
- Today’s practice is the dominant pale-teal hero.
- Streak, progress, achievement, and future content follow in a single vertical rhythm.
- The primary tab bar remains visible.

### Games

- Editorial heading, search, and two predictable filter rows.
- One featured visual card followed by available and future sections.
- The same centered width and tab behavior as Dashboard.

### Workout

- Native detail title bar; primary tabs are removed.
- Header, progress, one focused card, and a compact action group.
- Preparation, placeholder, transition, and completion preserve identical alignment.

### Signal Shift Gameplay

Signal Shift deliberately leaves the standard Workout layout family once play begins. It is a native game scene, not a content screen:

- Native navigation and tab chrome are hidden for the complete runner.
- Instructions use one original geometric focal composition, a short premise, and one dominant action.
- Each round presents its rule as a centered focus moment, then transitions through a full-screen `3 → 2 → 1 → GO` countdown.
- Active play uses a fixed, non-scrolling composition: compact status, current rule, an expansive play field, and one transient feedback line.
- The play field has no card border, panel title, section wrapper, form control, or dashboard metric grid.
- Targets are shape-first pressables with generous invisible touch bounds. Visible labels are removed; authoritative shape, color, size, movement, direction, and rotation remain available to assistive technology.
- Lives are physical dots that dim and contract when lost. Score remains quiet. Combo appears only after a successful chain, then clears.
- Round and final results lead with a celebratory score moment, followed by a two-plus-one accuracy, reaction, and best-combo hierarchy plus the personal-best comparison. The arrangement avoids both tables and narrow three-column compression when Dynamic Type grows.

The target composition is approximately 5% status, 10% rule, 75% play field, and 10% transient feedback. Tutorial and result states use a single-column scroll boundary for Dynamic Type, keeping the result action reachable even after several preferred-text-size increases. Active play and countdown never scroll.

### Fullscreen and Settings

- Splash and state shells use a centered composition inside the same bounded scroll structure as content screens.
- Settings, About, Profile, and Progress placeholders use the same quiet background, bounded width, concise empty-state language, and large-text-safe vertical scrolling.

### Native Tree Composition Rule

NativePHP’s Blade collector materializes child native elements while anonymous component slots are being evaluated. A slot-based wrapper can therefore publish the child once at the caller and again inside the wrapper. Symptoms include phantom borders, unresponsive scrolling, incorrect alignment, and duplicated accessibility nodes.

For screen-level native layout boundaries:

- Author the `column` / `scroll-view` / centered `row` directly in the screen.
- Use self-closing components driven by scalar props.
- Use `@include` for conditional native overlays.
- Do not pass native element trees through anonymous Blade slots.

Scrollable application screens use a screen-filling column and a `h-full flex-1` scroll view. `EnnobleLayout` uses NativePHP’s bounded EDGE chrome path so the scroll view receives a finite viewport. Primary screens receive the bottom navigation only; detail screens receive the title bar only.

## Card Language

| Card | Surface | Radius | Use |
| --- | --- | --- | --- |
| Hero | `primary-surface` | 24 pt | Today’s practice and major focus |
| Workout | `surface-elevated` + border | 24 pt | Workout details and instructions |
| Game | `surface-elevated` + border | 24 pt | Playable library items |
| Metric | `secondary-surface` | 16 pt | Small statistics |
| Achievement | `surface-elevated` + border | 24 pt | Evidence-backed achievement state |
| Coming Soon | `secondary-surface` + border | 24 pt | Honest future preview |

Cards share a 20-point conceptual inset, a 16-point content rhythm, semantic colors, and a short ease-out appearance. Avoid nesting several bordered cards when spacing or a divider communicates the relationship.

## Button Language

- **Primary:** one main action per screen or card.
- **Secondary:** an alternative action that remains visually quieter.
- **Ghost:** Back, Skip, Cancel, and low-emphasis navigation.
- **Destructive:** irreversible exits or deletion, always paired with confirmation.
- **Loading:** preserves its width and disables repeat interaction.
- **Disabled:** uses semantic disabled surfaces and remains legible.

Buttons are content-led, typically 176–224 points wide in centered flows. Full-width buttons are reserved for cases where reach or compact width genuinely requires them. Icon-only controls require an accessibility label.

## Motion Language

Motion communicates continuity and state; it is never required to understand the interface.

| Token | Duration | Use |
| --- | ---: | --- |
| Fast | 110 ms | Press feedback |
| Normal | 180 ms | Card and control transitions |
| Slow | 260 ms | Illustration and screen emphasis |
| Spring | 300 ms | Restrained native continuity |
| Success | 340 ms | Completion emphasis |
| Error | 180 ms | Recoverable error emphasis |

Onboarding illustrations use small scale and translation loops rather than playful character animation. Cards use short entrance easing. Navigation, dialogs, and sheets use native transitions. Reduced Motion resolves authored durations to zero and removes non-essential transforms.

Signal Shift uses motion only to communicate gameplay:

- Rule reveal: scale and opacity establish the new decision boundary.
- Countdown: each value owns the scene, expands, fades, and triggers preference-gated haptic feedback.
- Spawn: keyed targets enter as new native nodes and use restrained scale or directional motion.
- Correct: the selected wave disappears, a local particle burst expands, score floats briefly, and an active combo flashes.
- Wrong or missed: the play field shifts laterally, danger feedback appears briefly, and one life contracts.
- Completion: the score focus expands once before settling into readable evidence.

Reduced Motion keeps the same phases, timing meaning, rule copy, static movement-direction markers, feedback text, and evidence. It removes authored translation, looping, scale, and opacity durations.

### Sound Cue Architecture

The intended offline cue vocabulary is:

| Cue | Meaning | Current capability |
| --- | --- | --- |
| Countdown | One restrained pulse per count; confident release on GO | Designed, not played |
| Correct | Short bright confirmation | Designed, not played |
| Wrong or missed | Soft low rejection without alarm | Designed, not played |
| Combo | Brief rising accent at a configured milestone | Designed, not played |
| Completion | Warm resolved cadence | Designed, not played |

The installed stack still exposes no reviewed bundled-audio playback bridge and the repository contains no approved original cues. Sound remains a documented presentation contract only; haptics and visual feedback carry the current experience without pretending audio exists.

## Illustration Direction

Use original abstract geometry, strong negative space, circles, paths, and typed platform symbols. Illustrations may support a task but must not compete with its headline. Bundle every required asset locally. Never use emoji as interface iconography or copy another product’s artwork.

## Accessibility

- Native text must scale with system settings.
- Maintain at least 44-point interactive targets.
- Label icon-only controls and meaningful images.
- Decorative icons remain silent.
- Keep focus order aligned with visual order.
- Do not apply one accessibility label to a container with interactive children.
- Pair color with copy, icon, position, or shape.
- Keep Back and recovery actions available without gesture-only navigation.
- Respect Reduced Motion, sound, and haptic preferences.
- Run `assertAccessible()` for implemented states, then separately verify VoiceOver, TalkBack, large text, orientation, and real touch behavior before release.

## Screen State Checklist

Every screen must account for:

- Initial and active states.
- Loading where local work is perceptible.
- Empty and unavailable states.
- Completed state.
- Disabled controls.
- Recoverable errors.
- Light, Dark, and System appearance.
- Reduced Motion.
- Large text and narrow width.
- Offline operation.

## Contributor Checklist

Before adding a visual component:

1. Select semantic roles instead of authoring a component color.
2. Select an existing typography and spacing role.
3. Choose the correct layout family and card language.
4. Keep one clear primary action.
5. Avoid native child slots; prefer scalar props and direct screen composition.
6. Add initial, disabled, empty/error, and accessibility behavior.
7. Verify light and dark rendering, large text, and native scrolling.
8. Add or update Pest coverage and refresh simulator evidence.
