# Ennoble Architecture

## Verified Current State

Ennoble runs on Laravel 13.20.0, PHP 8.4.23, and local SQLite. NativePHP Mobile remains locked to `dev-element` at `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d`. Native UI remains the frozen project-owned path mirror based on upstream commit `ce3d8b760c89dd08e14baad8b05afd82494d3c46`, with only the documented iOS 18.2 manifest fix.

Prompt 2 adds the complete first-release domain and persistence foundation. Prompt 3 adds the reusable native application shell. Prompt 4 adds the first-launch onboarding journey. Prompt 5 replaces the Home placeholder with the local state-aware dashboard. Prompt 6 replaces the Games placeholder with a curated, searchable, filterable native library. Prompt 7 adds the reusable workout-session framework. Prompt 8 replaces the Signal Shift placeholder with the first production game while retaining Clear Thought as an honest placeholder. Prompts 5–8 do not change Composer, plugin registration, NativePHP configuration, or the Native UI mirror.

The application has fourteen native routes, including onboarding, the complete Home dashboard, the complete Games library, five shared workout routes, and the dedicated Signal Shift runner. It retains the four-tab `NativeLayout`, semantic light/dark tokens, settings-aware theme resolution, shared EDGE state components, typed platform icon catalogs, and reusable dialog/toast/haptic infrastructure. Clear Thought gameplay, the detailed Progress screen, Profile editing, and detailed Statistics or Achievements screens remain unimplemented.

The local database now contains the additive Ennoble schema and bundled definitions for:

- Signal Shift and Clear Thought.
- Three difficulty levels per game.
- Six local achievement definitions.
- Profiles, settings, workouts, resumable sessions, rounds, progress, statistics, streaks, and unlock history.

No profile or user-generated activity is seeded.

## Architectural Goals

- Fully offline business behavior.
- Laravel-first Eloquent persistence.
- Small domain services organized by product responsibility.
- Deterministic bundled definitions and scoring.
- Transactional checkpoints and idempotent completion.
- Rebuildable aggregates backed by authoritative session evidence.
- Typed enums for finite state.
- UI-independent behavior testable without a simulator.
- No repositories, remote providers, queues, or speculative inheritance.

## Implemented Directory Shape

```text
app/
  Domain/
    Achievements/
      AchievementService.php
    Games/
      Contracts/
        GameScoringService.php
        ScoringResult.php
      ClearThought/
        ClearThoughtAnswerValidator.php
        ClearThoughtScoringService.php
      SignalShift/
        SignalShiftGameService.php
        SignalShiftRule.php
        SignalShiftRuleEngine.php
        SignalShiftScoringService.php
      GameSessionService.php
    Onboarding/
      OnboardingService.php
    Profile/
      ProfileService.php
    Progress/
      ProgressService.php
    Settings/
      SettingsService.php
    Statistics/
      StatisticsService.php
    Workout/
      WorkoutService.php
  Enums/
  Models/
database/
  factories/
  migrations/
  seeders/
tests/
  Feature/
    Database/
    Domain/
    Persistence/
  Unit/
    Domain/
```

The native application layer now adds:

```text
app/
  Icons/
  NativeComponents/
    Screens/
  NativeLayouts/
  NativeUI/
    Dialogs/
    Feedback/
    Navigation/
    Screens/
    Home/
    Theme/
    Tokens/
resources/
  animations/
  audio/
  fonts/
  icons/
  illustrations/
  views/
    components/native/
    native/screens/
routes/
  mobile.php
tests/
  Fixtures/Native/
```

The asset directories remain structural and do not ship external artwork, animation, sound, icon, or font files. Prompt 4's original illustration placeholders are composed from typed native icons and geometric EDGE surfaces so later bundled artwork can replace them without changing screen behavior.

## Native Application Shell

### Routing and Chrome

`routes/mobile.php` registers:

- `/splash`
- `/onboarding`
- `/`
- `/workout`
- `/workout/preparation/{session}`
- `/workout/game/{session}`
- `/workout/game/signal-shift/{session}`
- `/workout/transition/{item}`
- `/workout/complete/{workout}`
- `/games`
- `/progress`
- `/profile`
- `/settings`
- `/about`

The root route remains Home so the existing NativePHP `start_url` remains unchanged. Home redirects an incomplete local profile to `/onboarding`; returning users receive the current local dashboard. Direct onboarding navigation by a completed profile replaces back to Home. Workout routes use hidden tab chrome and replace navigation across introduction, preparation, container, transition, and completion so stale phases do not accumulate in the native stack. Splash remains an explicit navigation-verification route.

`EnnobleLayout` opts into installed v4 native chrome. It provides:

- A reusable native top bar using per-screen title, subtitle, back, and right-action overrides.
- Home, Games, Progress, and Profile tabs.
- Typed SF Symbol and Material icon enums.
- URL-derived active-tab state.
- Native replace navigation for tab switches.
- Layout-owned safe-area behavior.
- Hidden tab chrome on Settings and About detail placeholders.

An additional shared inline EDGE top bar supports explicit left and right action slots for future chrome-less compositions. It uses a 44-point minimum target and a fixed `goBack` callback contract.

### Screen Container and States

`resources/views/components/native/screen-container.blade.php` is the common content boundary. It owns:

- Optional safe-area handling for chrome-less screens.
- Screen padding and component spacing from `DesignTokens`.
- Scroll and fixed-content variants.
- Loading, empty, error, and active content states.
- A reusable overlay slot.

Shared loading, empty, error, icon, top-bar, modal, and bottom-sheet components compose within this boundary. The error state exposes retry content and an illustration placeholder without shipping an illustration asset.

### Theme and Motion

`config/native-ui.php` defines Ennoble's semantic light and dark palettes and Native UI radius/font tokens. `ThemeManager` reads Prompt 2's local setting and supports:

- System mode by retaining distinct light and dark token blocks.
- Explicit light mode by applying the light palette to both renderer appearances.
- Explicit dark mode by applying the dark palette to both renderer appearances.
- Current semantic token lookup for native chrome.
- Reduced-motion-aware duration resolution.

The installed renderer still derives platform appearance from the operating system. Explicit preferences therefore force Ennoble's semantic colors and chrome colors, but exact system-bar appearance remains a device-verification item.

`DesignTokens` centralizes typography, spacing, corner radius, elevation, motion duration, opacity, icon size, screen padding, component spacing, and minimum touch target values. Onboarding uses those durations for restrained progress, illustration, card, and step transitions. Home uses the same values for section appearance and native-thread press feedback. Signal Shift derives stimulus translation and duration from the same tokens plus its configured speed modifier. Reduced Motion resolves authored durations and stimulus translation to zero without removing rule meaning.

### Feedback and Dialog Infrastructure

`HapticService` honors Prompt 2's local haptic preference and calls the installed core `Device::vibrate()` capability. Semantic intents are typed as success, error, warning, selection, and impact, but the current core bridge provides one generic short vibration rather than distinct platform patterns.

`ToastService` supports success, error, warning, and information with a visible text prefix before calling the native toast bridge.

`DialogService` creates native alerts and destructive confirmations. `InteractsWithDialogs` plus the shared dialog host provide reusable modal and bottom-sheet state without product business logic.

## Layer Responsibilities

### Eloquent Models

Models own persistence mapping only:

- Typed relationships.
- Enum, JSON, boolean, date, and datetime casts.
- Database-default mirrors.
- Explicit mass-assignment fields.
- Small reusable scopes such as `playable`, `active`, `completed`, `resumable`, `forDate`, and `overall`.

Business operations remain in domain services.

### Enums

Stable backed enums cover:

- Game type and availability.
- Difficulty.
- Workout and session status.
- Round outcome.
- Achievement criteria.
- Theme and training goal.
- Clear Thought mode.
- Trained skill keys.

These values are database contracts and must not be renamed casually after release.

### Factories

Factories produce purposeful domain states rather than unrelated random values. Named completed states exist where they make tests clearer. Seeded definition tests normally reuse the migration-installed records rather than creating conflicting fake definitions.

## Domain Services

### Profile and Settings

`ProfileService` owns Ennoble's single local profile:

- Normalizes the optional display name and enforces the shared 40-character maximum.
- Creates or updates the unique `local` profile.
- Ensures safe default settings exist in the same transaction.

`SettingsService` owns:

- Theme.
- Sound.
- Haptics.
- Reduced motion.
- Daily reminder preference.
- Bounded accessibility preferences.

Unsupported accessibility keys are discarded instead of being silently treated as product capability.

### Onboarding

`OnboardingService` composes the existing profile and settings services rather than duplicating their persistence rules. In one transaction it:

1. Normalizes and stores the optional display name.
2. Stores the selected training goal and difficulty.
3. Stores theme, sound, haptics, and reduced-motion preferences.
4. Marks the local profile complete with `onboarding_completed_at`.

`Onboarding` owns only native screen state, validation, feedback, theme preview, step navigation, and final navigation. Goal and difficulty selections reject forged enum values. The optional display name uses the domain limit. A failed transaction leaves the user on the final step with a recoverable message.

The UI is one native screen with eight conditional steps and reusable progress, feature-card, illustration-placeholder, input, carousel, and summary-row Blade components. It uses genuine native paging, radio groups, toggles, text input, buttons, and typed platform icons. Selection and completion haptics pass through the existing preference-aware `HapticService`.

### Home Dashboard

`Home` remains a presentation boundary. It:

1. Enforces onboarding and applies the saved theme.
2. Resolves a reusable device-time greeting and friendly display-name fallback.
3. Calls `WorkoutService::generateToday()` and `history()` for the Today card, workout state, seven-day activity, and returning-user state.
4. Calls `StatisticsService::overview()` and `personalBests()` for streak and personal-best previews.
5. Calls `ProgressService::currentSkillValues()` for evidence-backed skill highlights.
6. Calls `AchievementService::latestUnlock()` for the newest persisted unlock.
7. Calls `ProfileService` and `SettingsService` for the local profile and Reduced Motion preference.

The component maps those domain results into serializable native view state. It uses NativePHP's verified lazy-screen attribute for an immediate initial loading frame. It does not score rounds, start sessions, update statistics, evaluate achievements, or write new persistence rules. Workout generation remains the only intentional domain mutation on Home and is idempotent per profile/local date.

Reusable EDGE components own the greeting, section headers, section loading, Today workout, streak, progress, achievement, and Coming Soon cards. The full dashboard and each data section expose loading and recoverable error states independently. Empty states distinguish no streak, no progress evidence, no workout history, no personal best, and no achievement unlock.

The Today CTA triggers preference-aware haptics and navigates to `/workout`. The introduction remains side-effect free; its Begin or Resume action creates or retrieves a real Signal Shift session or the explicit Clear Thought framework placeholder according to the current item. Completed workouts expose a disabled Completed Today action. Coming Soon cards provide native-thread press feedback and open the existing shared bottom-sheet host without navigation or session creation.

### Games Library

`Games` is a native presentation boundary. It:

1. Enforces onboarding and applies the saved local theme and Reduced Motion preference.
2. Loads only the two bundled playable `Game` definitions.
3. Uses `WorkoutService::levelForProfile()` and `estimatedGameDurationMinutes()` so profile-level difficulty resolution and round-based duration estimates are not duplicated in the screen.
4. Uses `StatisticsService::gamePreviews()` for personal best, completion count, started-session count, completion rate, and last-played evidence.
5. Maps the two persisted games and six presentation-only future definitions into serializable view state.
6. Applies in-memory category and search filtering across title, category, and description.

Signal Shift is both the featured card and one of the two available cards. Clear Thought is the other available card. The six Coming Soon definitions remain application presentation data because they are unavailable and must not enter the persisted playable-content lifecycle.

Reusable EDGE components own the featured card, playable card, Coming Soon card, illustration placeholder, statistic tile, badge, category chip, and search input. The existing screen container, section header, loading card, empty/error states, dialog host, semantic tokens, typed icons, toast service, and haptic service are reused.

Playable actions trigger preference-aware impact feedback and navigate to the workout introduction. Beginning there creates a resumable real Signal Shift attempt for its item; only Clear Thought uses the non-evidentiary placeholder path. Coming Soon cards provide native-thread press feedback and selection haptics, then open the shared bottom sheet without navigation or persistence. Search and filters never mutate SQLite.

Initial loading, complete-catalog, filtered, no-search-result, no-category-match, no-history, no-statistics, statistics-error, and full recoverable-error states are represented. Reduced Motion resolves authored appearance and press transforms to static values. The in-process accessibility audit covers the complete and conditional trees, while platform reading order, scalable-text layout, and visual behavior remain device-verification work.

### Workout

Five shared NativeComponents and one dedicated game component own the workout presentation lifecycle:

1. `WorkoutIntroduction` loads the deterministic daily sequence and presents duration, difficulty, skills, Begin, and Resume without starting a session during mount.
2. `WorkoutPreparation` presents game-specific guidance and a poll-driven three-second countdown, then persists the prepared checkpoint.
3. `SignalShiftGame` owns the real instructions, optional tutorial, rule shifts, waves, timer, lives, combo, scoring feedback, pause/resume, restart, failure, completion, and results. `WorkoutGameContainer` remains the explicit non-evidentiary Clear Thought placeholder.
4. `WorkoutTransition` presents either persisted Signal Shift performance or the truthful placeholder state plus the next game. It advances after three poll ticks or a Continue action; Reduced Motion disables automatic advancement.
5. `WorkoutComplete` presents training time and completed steps plus only the score and accuracy supported by real evidence.

Reusable EDGE components own the header, progress, countdown, game container, transition card, completion card, pause sheet, and footer. The screens use typed icons, semantic theme tokens, native modal/bottom-sheet hosts, preference-aware haptics, hidden tab chrome, accessibility labels, and recoverable errors.

Signal Shift intentionally diverges from the shared card-based workout composition only inside its dedicated runner. `SignalShiftGame` hides both navigation bars and renders:

- Scrollable instruction, tutorial, rule-reveal, round-result, failure, and final-result states for scalable text.
- A fixed, non-scrolling full-screen countdown state.
- A fixed active-play state with compact HUD, physical lives, quiet score, transient combo, rule copy, and a dominant shape play field.
- Application-owned scalar-prop Blade components that encapsulate canvas/shape primitives. This preserves the project’s native-tree composition rule and works around the installed validator’s direct shape allowlist without editing the frozen Native UI mirror.

The non-playing scroll view owns a single outer column so its intrinsic Dynamic Type height remains scrollable. Result metrics use a two-plus-one hierarchy rather than three compressed equal columns; this preserves readable values and labels at large preferred text sizes while keeping the action reachable below.

Presentation-only checkpoint fields retain the active countdown and transient feedback across process interruption. They do not enter `game_rounds`, scoring, statistics, progress, achievements, or difficulty selection. The existing `SignalShiftGameService`, `SignalShiftRuleEngine`, `SignalShiftScoringService`, and `GameSessionService` remain authoritative and unchanged by the Game-UX-1 redesign.

`WorkoutService`:

- Loads or creates one workout for a supplied local date.
- Requires both playable bundled definitions.
- Selects the profile's active difficulty level for each game.
- Resolves an Adaptive profile to the deterministic Intermediate starting levels until performance-based adaptation is implemented, without changing the stored profile preference.
- Creates Signal Shift then Clear Thought in deterministic order.
- Returns the same workout when generation is repeated.
- Estimates two configured rounds per minute, bounded to the product's 5–10 minute duration promise.
- Hydrates complete resume state.
- Refuses premature completion.
- Finalizes the workout summary idempotently and updates statistics, streaks, and achievements only when gameplay evidence exists.
- Leaves game-specific restart semantics to the session service so a Signal Shift restart cannot erase completed workout evidence.
- Exposes newest-first history.

Missing games or levels raise an explicit domain exception. The service never invents fallback content.

### Games and Sessions

`GameScoringService` is the only shared game contract because two real implementations exist. Both scoring services accept persisted round evidence and return the immutable normalized `ScoringResult`.

`SignalShiftScoringService` owns:

- Correct, incorrect, and missed outcomes.
- Accuracy.
- Compatible response-time average.
- Speed bonus.
- Combo bonus.
- Incorrect/missed penalties.
- Non-negative deterministic final score.

Accuracy dominates random rapid tapping through explicit penalties.

`SignalShiftRule` is the validated immutable rule value object. It supports target color, target shape, excluded shape, movement, size, rotation, speed, density, waves, and timing without encoding named rules in the UI.

`SignalShiftRuleEngine`:

- Requires exactly three bundled player-facing rounds.
- Produces deterministic waves from the level configuration.
- Guarantees exactly one eligible target and enough ineligible distractors.
- Keeps generated labels, direction, and target eligibility authoritative for both interaction and accessibility.

`SignalShiftGameService`:

- Rejects placeholders and non-Signal Shift sessions.
- Records correct, incorrect, and missed outcomes through `GameSessionService`.
- Stores round/wave and stimulus metadata with each immutable evidence row.
- Calculates live round and session metrics with the existing scoring service.
- Resolves previous-best and tutorial-completion evidence.
- Completes or restarts the attempt through the shared transactional lifecycle.

`ClearThoughtScoringService` owns:

- Correctness.
- Completion-time contribution.
- Attempts.
- Hint penalty.
- Deterministic result generation.

`ClearThoughtAnswerValidator` supports all three v1 modes using only explicit bundled accepted answers. It performs no remote or generative evaluation.

`GameSessionService` owns the lifecycle around those focused rules:

1. Start or resume an attempt.
2. Activate its workout and item when applicable.
3. Append each round and update the bounded checkpoint in one transaction.
4. Validate resumable/completable states.
5. Select the correct scoring implementation by `GameType`.
6. Finalize session metrics and its workout item.
7. Persist skill, statistics, and achievement evidence exactly once.
8. Start the implemented Signal Shift path or the remaining Clear Thought placeholder from one workout-item boundary.
9. Restart an unfinished real session by deleting only its rounds and resetting its evidence fields while preserving its identity and workout ownership.

It additionally owns a deliberately separate framework-placeholder path. Placeholder sessions persist prepared, paused, and elapsed-time checkpoints; completion stores no rounds, score, accuracy, personal best, skill progress, statistics, or achievement evidence. The normal scoring pipeline rejects placeholder sessions. It does not know about navigation, NativeComponents, EDGE, or layout.

### Progress

`ProgressService` stores append-only skill snapshots:

- A new skill starts from an internal neutral baseline only when first evidence is recorded.
- Values are bounded from 0 to 1000.
- A session may contribute at most one snapshot per skill.
- Current values are the latest persisted evidence per skill.
- New profiles return no fabricated current-skill data.

### Statistics and Streaks

`StatisticsService` owns:

- Accuracy with `null` for unavailable evidence.
- Compatible average response time.
- Overall and per-game completed-session aggregates.
- Completed-workout totals and training time.
- Personal best score and longest combo.
- Current and longest completed-workout streak.
- Evidence-backed daily summaries.
- Full aggregate rebuild from authoritative completed records.
- Read-only overall and per-game personal-best retrieval for lightweight presentation.
- Games-library previews combining per-game aggregates with started-session counts and latest-play evidence.

`statistics_recorded_at` markers on sessions and workouts prevent repeated completion calls from double counting. `scope_key` provides reliable overall/per-game uniqueness under SQLite.

### Achievements

`AchievementService`:

- Evaluates active local definitions only.
- Supports first workout, streak, accuracy, score, combo, and hint-free criteria.
- Uses persisted overall/per-game statistics and completed-session evidence.
- Stores observed value and threshold as unlock evidence.
- Inserts one unlock per profile and definition.
- Ignores inactive definitions.
- Returns the latest persisted unlock with its definition for lightweight presentation.

## Persistence and Transaction Model

SQLite foreign keys and unique constraints enforce core invariants:

- One local profile key.
- One nullable onboarding-completion timestamp per local profile.
- One settings row per profile.
- One workout per profile/date.
- One game and position per workout.
- One round number per session.
- One session-backed progress event per skill.
- One aggregate per profile/scope.
- One unlock per profile/achievement.

Transactions wrap multi-record operations. Completion calls may be repeated safely; they return the persisted result without duplicating progress, statistics, streaks, or unlocks.

## Bundled Content

NativePHP runs migrations during application startup, so the bundled definition migration invokes:

- `GameDefinitionSeeder`
- `GameLevelSeeder`
- `AchievementDefinitionSeeder`

The seeders use stable slugs/types and SQLite upserts. `DatabaseSeeder` calls the same content seeders for development parity but does not create a profile, workout, statistic, attempt, or test user.

Signal Shift ships versioned local level configuration for Beginner, Intermediate, and Advanced. Every difficulty defines three validated rules plus lives, combo milestone, palette, shapes, density, speed, waves, and timing. The additive content migration invokes the idempotent level seeder so both fresh installs and existing installs receive version 2 configuration without seeding user activity. Clear Thought challenge content remains deferred to its gameplay prompt.

## Offline Boundary

The domain layer has no HTTP client, remote API, authentication service, analytics SDK, advertisement SDK, cloud database, queue dependency, or runtime asset/content download.

All current inputs are:

- Native interactions from application-owned EDGE components.
- Bundled seeded definitions.
- Local SQLite records.
- Local time/date supplied by the caller or Laravel.

The project persists a sound preference and reserves `resources/audio`, but the installed core and registered Native UI plugin expose no bundled-audio playback bridge and no audio assets are present. Prompt 8 therefore adds no fake sound facade, remote asset, dependency, or platform patch. Actual cues remain a capability boundary requiring a reviewed local native bridge and original bundled files.

Game-UX-1 defines five future presentation events—countdown, correct, wrong or missed, combo milestone, and completion—but adds no playback call. Existing preference-gated haptics provide current tactile feedback for the same state changes.

## NativePHP Boundary

The NativePHP Mobile skill requires database seeding through migrations, which this implementation follows. No native build, simulator, or device command is needed to validate this PHP-only layer.

The frozen NativePHP compatibility boundary remains:

- `App\Providers\NativeServiceProvider` still allowlists only Native UI.
- No plugin was installed or changed.
- Root Composer repositories and constraints are unchanged.
- `packages/nativephp/native-ui` contains no Ennoble domain logic.

`php artisan native:validate` passes all fourteen application NativeComponents without warnings. The installed validator's static tag allowlist does not yet include every runtime-registered Native UI manifest type used by the application, so carousel, outlined text input, and Games filter chips are isolated behind application Blade components. The registered plugin manifest, in-process render tests, and `native:plugin:validate` remain the runtime evidence; the frozen mirror is unchanged.

## Failure Handling

- Missing bundled definitions fail explicitly and roll back workout generation.
- Invalid session transitions are rejected.
- Profile/session/workout evidence must share ownership.
- Invalid accuracy counts are rejected.
- Resume snapshots carry a version and remain bounded.
- Aggregate rebuilds do not alter authoritative rounds or completed sessions.
- Migration failures are not hidden by database resets.
- Dashboard section failures retain unrelated local previews and never expose raw exceptions.
- Invalid Coming Soon identifiers do nothing, and opening the workout introduction never starts a session.
- Missing playable Games definitions produce a recoverable full-library error rather than invented cards.
- Games statistics failures preserve the catalog, show unavailable evidence, and offer a focused retry.
- Invalid category, playable-game, or Coming Soon identifiers do nothing.
- Missing or foreign workout checkpoints render a recoverable state.
- Exit preserves the latest local checkpoint. Signal Shift restart clears only its unfinished attempt; placeholder restart clears only the active placeholder state.
- Placeholder sessions are excluded from game previews, statistics rebuilds, hint-free achievement evidence, and the real scoring pipeline.
- Invalid Signal Shift configuration or a missing/foreign checkpoint produces a recoverable native error instead of guessed gameplay.
- Tutorial taps never enter the round evidence table.

## Verification Boundary

Pest tests cover migrations, upgrade preservation, rollback/reapply evidence, seed idempotency, constraints, relationships, casts, enums, profile/settings persistence, scoring, answer validation, checkpoints, completion, workout generation, Adaptive fallback, per-game duration resolution, progress, statistics, Games preview aggregation and completion rate, streaks, achievements, idempotency, native route registration, navigation/chrome, shared state rendering, settings-aware theme application, reduced motion, feedback bridges, dialogs, typed design tokens, onboarding launch guards, all eight onboarding steps, dashboard greetings and state variants, Games featured/available/future sections, filtering, offline search, conditional empty/error states, all workout phases, Signal Shift rule combinations and deterministic generation, every bundled difficulty, tutorial behavior, correct/incorrect/missed outcomes, lives, combo, timer, failure/restart, pause/exit/resume, completion evidence, personal best, mixed real/placeholder workout completion, Coming Soon behavior, and in-process accessibility audits.

These are Laravel in-process/database tests. They are not Android, iOS, simulator, physical-device, VoiceOver, TalkBack, offline-airplane-mode, or visual tests.

## Next Implementation Boundary

Signal Shift is the reference game and Clear Thought remains the only workout placeholder. The next game prompt can reuse the real session-start boundary, transactional evidence model, results/statistics/progress pipeline, and shared workout navigation without copying Signal Shift-specific rules. Detailed Progress, Profile editing, Statistics or Achievements screens, notifications, authentication, and remote capability remain unimplemented.

Signal Shift now has iPhone 17 Simulator evidence for repeated play, relaunch/re-entry, light/dark results and gameplay, large Dynamic Type, Reduced Motion, and Accessibility Inspector order/audit. Physical-device VoiceOver, Android/TalkBack, compact-device, bundled-audio, and physical performance/haptic evidence remain release work.
