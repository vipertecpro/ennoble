# Ennoble Architecture

## Verified Current State

Ennoble runs on Laravel 13.20.0, PHP 8.4.23, and local SQLite. NativePHP Mobile remains locked to `dev-element` at `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d`. Native UI remains the frozen project-owned path mirror based on upstream commit `ce3d8b760c89dd08e14baad8b05afd82494d3c46`, with only the documented iOS 18.2 manifest fix.

Prompt 2 adds the complete first-release domain and persistence foundation. Prompt 3 adds the reusable native application shell without changing Composer, plugin registration, NativePHP configuration, or the Native UI mirror.

The application now has seven native placeholder routes, a four-tab `NativeLayout`, semantic light/dark tokens, settings-aware theme resolution, shared EDGE state components, typed platform icon catalogs, and reusable dialog/toast/haptic infrastructure. Gameplay, Today, Games, Progress, Profile, onboarding, animations, illustrations, and business UI remain unimplemented.

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
        SignalShiftScoringService.php
      GameSessionService.php
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

The empty asset directories contain only structural `.gitkeep` files. No placeholder illustration, animation, sound, icon asset, or font ships from Prompt 3.

## Native Application Shell

### Routing and Chrome

`routes/mobile.php` registers:

- `/splash`
- `/`
- `/games`
- `/progress`
- `/profile`
- `/settings`
- `/about`

The root route is the Home placeholder so the existing NativePHP `start_url` remains unchanged. Splash is an explicit navigation-verification route rather than an onboarding flow.

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

`DesignTokens` centralizes typography, spacing, corner radius, elevation, motion duration, opacity, icon size, screen padding, component spacing, and minimum touch target values. No gameplay motion is implemented.

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

- Normalizes and validates the display name.
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

### Workout

`WorkoutService`:

- Loads or creates one workout for a supplied local date.
- Requires both playable bundled definitions.
- Selects the profile's active difficulty level for each game.
- Creates Signal Shift then Clear Thought in deterministic order.
- Returns the same workout when generation is repeated.
- Hydrates complete resume state.
- Refuses premature completion.
- Finalizes the workout summary, statistics, streak, and achievements idempotently.
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

It does not know about navigation, NativeComponents, EDGE, or layout.

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

`statistics_recorded_at` markers on sessions and workouts prevent repeated completion calls from double counting. `scope_key` provides reliable overall/per-game uniqueness under SQLite.

### Achievements

`AchievementService`:

- Evaluates active local definitions only.
- Supports first workout, streak, accuracy, score, combo, and hint-free criteria.
- Uses persisted overall/per-game statistics and completed-session evidence.
- Stores observed value and threshold as unlock evidence.
- Inserts one unlock per profile and definition.
- Ignores inactive definitions.

## Persistence and Transaction Model

SQLite foreign keys and unique constraints enforce core invariants:

- One local profile key.
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

Clear Thought challenge content and Signal Shift gameplay configuration beyond the level foundation are intentionally deferred to their gameplay prompts. The schema and validation/scoring boundaries are ready for that content.

## Offline Boundary

The domain layer has no HTTP client, remote API, authentication service, analytics SDK, advertisement SDK, cloud database, queue dependency, or runtime asset/content download.

All current inputs are:

- Method parameters from a future local native UI.
- Bundled seeded definitions.
- Local SQLite records.
- Local time/date supplied by the caller or Laravel.

## NativePHP Boundary

The NativePHP Mobile skill requires database seeding through migrations, which this implementation follows. No native build, simulator, or device command is needed to validate this PHP-only layer.

The frozen NativePHP compatibility boundary remains:

- `App\Providers\NativeServiceProvider` still allowlists only Native UI.
- No plugin was installed or changed.
- Root Composer repositories and constraints are unchanged.
- `packages/nativephp/native-ui` contains no Ennoble domain logic.

`php artisan native:validate` passes all seven Prompt 3 NativeComponents without warnings.

## Failure Handling

- Missing bundled definitions fail explicitly and roll back workout generation.
- Invalid session transitions are rejected.
- Profile/session/workout evidence must share ownership.
- Invalid accuracy counts are rejected.
- Resume snapshots carry a version and remain bounded.
- Aggregate rebuilds do not alter authoritative rounds or completed sessions.
- Migration failures are not hidden by database resets.

## Verification Boundary

Pest tests cover migrations, upgrade preservation, rollback/reapply evidence, seed idempotency, constraints, relationships, casts, enums, profile/settings persistence, scoring, answer validation, checkpoints, completion, workout generation, progress, statistics, streaks, achievements, idempotency, native route registration, navigation/chrome, shared state rendering, settings-aware theme application, reduced motion, feedback bridges, dialogs, typed design tokens, and in-process accessibility audits.

These are Laravel in-process/database tests. They are not Android, iOS, simulator, physical-device, VoiceOver, TalkBack, offline-airplane-mode, or visual tests.

## Next Implementation Boundary

Prompt 4 may add onboarding and local-profile UI on top of this shell. It must reuse `EnnobleLayout`, the screen container, semantic tokens, typed icons, and Prompt 2 services. It must not implement gameplay, Today, Games, Progress, statistics, or achievement UI ahead of their approved stages.
