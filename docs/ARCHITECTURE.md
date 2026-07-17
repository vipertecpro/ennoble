# Ennoble Architecture

## Verified Starting Point

The current repository is Laravel 13.20.0 on PHP 8.4.23 with SQLite. NativePHP Mobile is installed from `dev-element`, Native UI from `dev-feat/webview-element`, and Camera at 1.0.3. SuperNative and EDGE classes exist in installed source, but the application has no NativeComponents, native routes, product models, or product migrations.

The NativePHP plugin provider has not been published, so installed plugins are not currently included in native builds. This document describes the intended application architecture; it does not imply that the build blocker is resolved.

## Architectural Goals

- Fully native, offline-first screens.
- Small, explicit domain services rather than business logic in UI components.
- SQLite as the authoritative local store.
- Transactional, resumable workouts and game sessions.
- Deterministic bundled content and scoring.
- Testable domain behavior without a simulator.
- No speculative interfaces or distributed-system patterns.

## Proposed Directory Shape

Use Laravel and the installed NativePHP conventions as implementation stages introduce files:

```text
app/
  Enums/
  Models/
  NativeComponents/
    Games/
  NativeLayouts/
  Services/
    Achievements/
    Games/
    Progress/
    Workouts/
database/
  factories/
  migrations/
  seeders/
resources/
  fonts/
  views/
    native/
      components/
      games/
routes/
  mobile.php
tests/
  Feature/
  Unit/
```

Do not create every folder up front. Add a directory when its first concrete class or view is implemented. Prefer focused services under `app/Services`; introduce actions only when an operation has a clear single-purpose boundary.

## UI Layer

### Native Routes

Register mobile destinations with `Route::native()` in a dedicated `routes/mobile.php` once the installed integration path has been verified. The expected route groups are:

- Today and workout summary.
- Games library and Coming Soon sheet.
- Signal Shift play and result.
- Clear Thought play and result.
- Progress and achievements.
- Profile, settings, about, and reset confirmation.

Routes carry identifiers, not hydrated Eloquent models or large game-state payloads. A component loads authoritative state through an injected service.

### NativeComponents

NativeComponent classes own:

- View-facing state.
- Lifecycle hydration.
- User interaction callbacks.
- Navigation intent.
- Native events.
- Loading, completion, disabled, and error presentation.

They do not calculate scores, mutate multiple aggregates independently, evaluate achievements, or embed content-selection algorithms.

### EDGE Templates and Reusable Components

EDGE Blade views render the native tree using verified `native:*` elements and Tailwind-style utility classes supported by the installed parser. Reusable view fragments cover repeated cards, metric rows, progress summaries, feedback banners, and empty states.

Use a `NativeLayout` for the four-section application shell after route and chrome behavior is verified. Gameplay screens may hide shared navigation to provide a focused full-screen experience. Layout-managed safe areas must not be duplicated inside child screens.

## Domain Layer

### Daily Workout Generation

`DailyWorkoutGenerator` loads or creates one workout for a local date. It selects one eligible session for each playable game using profile preference, recent performance, and deterministic fallback rules. A unique date constraint prevents duplicate daily workouts.

### Game Services

- `SignalShiftScoringService` calculates accuracy, response contribution, combo, mistakes, and final score from recorded rounds.
- `ClearThoughtScoringService` evaluates the explicit accepted-answer payload, hint use, attempts, completion time, and final score.
- `DifficultyProgressionService` recommends the next bounded difficulty using recent evidence. It never rewrites historical sessions.

Scoring inputs and outputs should use typed value objects or documented array shapes when that materially improves safety. Do not introduce interfaces until more than one implementation or a real system boundary exists.

### Completion and Progress

`GameSessionCompletionService` validates that a session can complete, finalizes its summary, advances any workout item, and coordinates downstream updates in one database transaction.

`StreakCalculator` derives current and longest streak from completed daily workouts and persists the current aggregate for fast display.

`AchievementEvaluator` evaluates only documented local criteria and inserts unlocks idempotently.

`StatisticsAggregator` updates or rebuilds skill scores and presentation metrics from completed results. Stored aggregates are caches of authoritative sessions/results and must remain rebuildable.

## Persistence Layer

Eloquent models represent the proposed tables in `DATABASE_PLAN.md`. Use typed relationships, enum casts for finite states, date/datetime casts, and JSON casts only for bounded snapshots or content payloads. Frequently filtered foreign keys, dates, statuses, and slugs require indexes.

Bundled games, challenges, and achievement definitions are seeded through dedicated migrations. On-device startup runs migrations; `DatabaseSeeder` is not an installation mechanism.

## Resumable Session Lifecycle

The application writes a checkpoint after every meaningful round or answer:

1. Begin a SQLite transaction.
2. Lock or reload the active session and verify it is still resumable.
3. Append the immutable `round_results` row.
4. Update `game_sessions.current_round`, summary counters, and bounded `state_snapshot`.
5. Update the linked `daily_workout_item` status when appropriate.
6. Commit before presenting the next round.

The snapshot contains only data required to resume presentation and rules: content/challenge identifiers, deterministic seed where used, current round, lives or mistake allowance, combo, remaining items, and mode-specific interaction state. It must not duplicate full result history.

On resume, the service validates snapshot version and referenced seeded content. If recovery is unsafe, the user receives an honest restart option; the application never fabricates completion.

Session completion runs in a separate idempotent transaction that:

- Marks the session complete once.
- Stores final metrics.
- Completes the workout item.
- Completes the workout when both items are done.
- Updates streak and skill aggregates.
- Inserts achievement unlocks without duplicates.

## Settings and Local Profile

The single `profiles` row is the local user identity and preference record. Settings do not require a separate table in v1. Services read preferences from this model, while components expose validated controls.

Reset Progress must run transactionally. It deletes user-generated workout/session/progress/unlock data, retains or recreates the local profile according to the confirmed product behavior, and preserves seeded content.

## Existing Scaffold Boundaries

The current `users`, password-reset, session, cache, and queue tables are Laravel scaffold artifacts. Ennoble v1 does not use remote authentication or background queues. They remain untouched in Prompt 1 and require an explicit later cleanup decision after NativePHP bootstrap requirements are proven.

The existing web route and welcome view prove Laravel can boot, but they are not the mobile application shell. No new primary experience should be built on that route.

## Failure Handling

- Treat missing seeded content or invalid snapshots as recoverable local errors with restart guidance.
- Wrap multi-record mutations in transactions and make completion/unlock operations idempotent.
- Do not hide migration failures or silently reset the database.
- Do not require connectivity for retries or recovery.
- Preserve the last committed checkpoint if rendering or navigation fails after a write.

## Authoritative References

- [NativePHP Mobile v4 Native UI](https://nativephp.com/docs/mobile/4/the-basics/native-ui)
- [NativePHP Mobile v4 Routing](https://nativephp.com/docs/mobile/4/the-basics/routing)
- [NativePHP Mobile v4 Databases](https://nativephp.com/docs/mobile/4/digging-deeper/databases)
- [NativePHP Mobile v4 Testing](https://nativephp.com/docs/mobile/4/testing/introduction)
- [Laravel 13 Eloquent](https://laravel.com/docs/13.x/eloquent)
- [Laravel 13 Database](https://laravel.com/docs/13.x/database)
- [Laravel 13 Service Container](https://laravel.com/docs/13.x/container)
