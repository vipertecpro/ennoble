# Ennoble Architecture

## Verified Current State

Ennoble runs on Laravel 13.20.0, PHP 8.4.23, and local SQLite. NativePHP Mobile remains locked to `dev-element` at `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d`. Native UI remains the frozen project-owned path mirror based on upstream commit `ce3d8b760c89dd08e14baad8b05afd82494d3c46`, with only the documented iOS 18.2 manifest fix.

Prompt 2 adds the complete first-release domain and persistence foundation without changing Composer, plugin registration, NativePHP configuration, or the Native UI mirror. There are still no Ennoble NativeComponents, native routes, EDGE views, navigation, gameplay screens, animations, illustrations, or assets.

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

The application intentionally has no `app/NativeComponents`, `app/NativeLayouts`, `resources/views/native`, or `routes/mobile.php` yet.

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

`php artisan native:validate` is expected to pass with the informational no-NativeComponents warning until Prompt 3 creates the native shell.

## Failure Handling

- Missing bundled definitions fail explicitly and roll back workout generation.
- Invalid session transitions are rejected.
- Profile/session/workout evidence must share ownership.
- Invalid accuracy counts are rejected.
- Resume snapshots carry a version and remain bounded.
- Aggregate rebuilds do not alter authoritative rounds or completed sessions.
- Migration failures are not hidden by database resets.

## Verification Boundary

Pest tests cover migrations, upgrade preservation, rollback/reapply evidence, seed idempotency, constraints, relationships, casts, enums, profile/settings persistence, scoring, answer validation, checkpoints, completion, workout generation, progress, statistics, streaks, achievements, and idempotency.

These are Laravel in-process/database tests. They are not Android, iOS, simulator, physical-device, VoiceOver, TalkBack, offline-airplane-mode, or visual tests.

## Next Implementation Boundary

Prompt 3 may consume this domain layer to build the native design system and application shell. It must not duplicate scoring, checkpoint, aggregation, or achievement logic inside NativeComponents. No gameplay content or screens should be inferred from the existence of this foundation.
