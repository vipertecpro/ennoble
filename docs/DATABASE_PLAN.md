# Ennoble Database Plan

## Goals

The v1 schema should support a single local profile, bundled content, resumable daily workouts, granular game evidence, aggregates, streaks, and achievements without creating a table for every concept.

This is a proposal only. Prompt 1 creates no migrations, models, factories, or seeders.

## Storage Rules

- SQLite is the only core data store.
- User-generated state remains local and must work offline.
- Foreign keys are enabled and relationships use conventional Laravel keys.
- Finite states are represented by PHP enums and stored as stable strings.
- Timestamps are stored consistently and local workout dates are stored separately from event datetimes.
- JSON is limited to versioned, bounded content payloads and resume snapshots. Queryable metrics use columns.
- Seeded definitions are versioned through migrations; user-generated history is never overwritten by a content refresh.

## Proposed Tables

### `profiles`

One row containing local identity and preferences.

| Column | Purpose |
| --- | --- |
| `id` | Primary key; v1 expects a singleton row |
| `display_name` | Local name shown in the application |
| `training_goal` | Finite goal enum |
| `difficulty_preference` | Finite preference enum |
| `sound_enabled` | Boolean |
| `haptics_enabled` | Boolean |
| `theme_preference` | `system`, `light`, or `dark` |
| `reduced_motion` | Boolean |
| `onboarding_completed_at` | Nullable completion timestamp |
| timestamps | Creation/update auditing |

The profile also stores settings, avoiding a separate settings table. It is user-generated and retained during a progress-only reset if product behavior confirms that preference.

### `games`

Seeded definitions for playable and Coming Soon games.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `slug` | Stable unique identifier |
| `name` | Product name |
| `description` | Bundled product copy |
| `status` | `playable` or `coming_soon` |
| `sort_order` | Stable library order |
| `skill_keys` | Bounded JSON list of trained skills |
| `configuration` | Versioned bounded defaults where code constants are insufficient |
| timestamps | Content version auditing |

Indexes: unique `slug`; index `(status, sort_order)`. Seeded content is retained across Reset Progress.

### `challenges`

Seeded, versioned content or rule definitions used by game sessions.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `game_id` | Owning game |
| `slug` | Stable identifier within a game |
| `mode` | Game-specific finite mode |
| `difficulty` | Bounded integer or stable enum value |
| `content_version` | Integer schema/content version |
| `prompt` | Optional local prompt text |
| `payload` | Bounded JSON instructions, options, and accepted answers |
| `explanation` | Optional educational explanation |
| `hint` | Optional local hint |
| `is_active` | Whether new sessions may select it |
| timestamps | Content auditing |

Indexes: unique `(game_id, slug)`; index `(game_id, mode, difficulty, is_active)`. Seeded challenges are not deleted when historical sessions reference them; obsolete content is marked inactive.

A separate `game_levels` table is not needed for v1. Difficulty is stored on challenges and snapshotted on sessions. Progression thresholds live in a tested service/configuration until the product needs editable level definitions.

### `daily_workouts`

One generated workout per local calendar date.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `profile_id` | Local profile |
| `workout_date` | Local date used for uniqueness |
| `status` | `pending`, `in_progress`, or `completed` |
| `generation_version` | Generator version for reproducibility |
| `started_at` | Nullable start |
| `completed_at` | Nullable completion |
| `training_seconds` | Final aggregate |
| `accuracy` | Nullable final normalized accuracy |
| `summary` | Bounded JSON for non-queryable result display |
| timestamps | Auditing |

Indexes: unique `(profile_id, workout_date)`; index `(profile_id, status, workout_date)`. User-generated. Reset Progress deletes it.

### `daily_workout_items`

Ordered game assignments within a daily workout.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `daily_workout_id` | Owning workout |
| `game_id` | Assigned game |
| `position` | Stable sequence, one-based or zero-based consistently |
| `status` | `pending`, `in_progress`, or `completed` |
| `difficulty` | Assigned difficulty snapshot |
| `configuration` | Bounded generated round/session configuration |
| `started_at` | Nullable start |
| `completed_at` | Nullable completion |
| timestamps | Auditing |

Indexes: unique `(daily_workout_id, position)`; index `(daily_workout_id, status)`. User-generated and cascade-deleted with its workout.

### `game_sessions`

One attempt for a game, optionally linked to a daily item.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `profile_id` | Local profile |
| `game_id` | Played game |
| `daily_workout_item_id` | Nullable daily assignment; null for free play |
| `status` | `in_progress`, `completed`, `abandoned`, or `invalid` |
| `mode` | Session mode |
| `difficulty` | Difficulty snapshot |
| `snapshot_version` | Resume-payload format |
| `current_round` | Resume cursor |
| `state_snapshot` | Bounded JSON needed to resume |
| `score` | Nullable final score |
| `accuracy` | Nullable final accuracy |
| `average_response_ms` | Nullable compatible metric |
| `correct_count` | Final/rolling count |
| `incorrect_count` | Final/rolling count |
| `hint_count` | Clear Thought hint usage |
| `best_combo` | Signal Shift combo result |
| `started_at` | Start |
| `last_interaction_at` | Latest committed checkpoint |
| `completed_at` | Nullable completion |
| timestamps | Auditing |

Indexes: `(profile_id, game_id, status, started_at)`, `(daily_workout_item_id, status)`, and `(game_id, score)`. User-generated. A daily item may have multiple attempts only if restart behavior is explicitly allowed; at most one may be active.

The resume snapshot contains no full history. It stores deterministic seed/content identifiers, remaining interaction order, current counters, and mode-specific UI state.

### `round_results`

Append-only evidence for each meaningful round or challenge response.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `game_session_id` | Owning session |
| `challenge_id` | Nullable seeded challenge |
| `round_number` | Ordered round index |
| `outcome` | `correct`, `incorrect`, `missed`, or mode-specific normalized result |
| `response_ms` | Nullable measured response time |
| `score_delta` | Round score contribution |
| `combo` | Nullable combo after the round |
| `hint_used` | Boolean |
| `response` | Bounded JSON answer/interaction evidence |
| `created_at` | Immutable event time |

Indexes: unique `(game_session_id, round_number)` and index `(challenge_id, outcome)`. Rows are appended inside the same transaction that updates the session checkpoint. They are deleted with the session during Reset Progress.

### `skill_scores`

Current rebuildable aggregates for each trained skill.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `profile_id` | Local profile |
| `skill_key` | Stable skill identifier |
| `score` | Normalized current score |
| `evidence_count` | Completed evidence included |
| `last_calculated_at` | Aggregate freshness |
| timestamps | Auditing |

Indexes: unique `(profile_id, skill_key)`. User-generated cache of completed session evidence. Reset Progress deletes it.

### `streak_states`

One current aggregate row per profile.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `profile_id` | Local profile |
| `current_streak` | Consecutive completed local dates |
| `longest_streak` | Historical maximum |
| `last_completed_date` | Latest counted workout date |
| timestamps | Auditing |

Index: unique `profile_id`. This row is rebuildable from completed daily workouts. Reset Progress deletes or zeroes it transactionally.

### `achievements`

Seeded achievement definitions.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `slug` | Stable unique identifier |
| `name` | Original display name |
| `description` | Unlock explanation |
| `category` | General or game-specific category |
| `criterion_type` | Evaluator key |
| `criterion` | Bounded versioned JSON threshold |
| `sort_order` | Collection order |
| `is_active` | Whether currently evaluated |
| timestamps | Content auditing |

Indexes: unique `slug`; index `(category, is_active, sort_order)`. Seeded and retained across resets.

### `achievement_unlocks`

Idempotent user unlock records.

| Column | Purpose |
| --- | --- |
| `id` | Primary key |
| `profile_id` | Local profile |
| `achievement_id` | Definition |
| `game_session_id` | Nullable triggering session |
| `daily_workout_id` | Nullable triggering workout |
| `unlocked_at` | Unlock time |
| `evidence` | Optional bounded JSON explanation |
| timestamps | Auditing |

Indexes: unique `(profile_id, achievement_id)`; index `(profile_id, unlocked_at)`. User-generated and deleted by Reset Progress.

## Relationship Summary

```text
profiles
  ├── daily_workouts ── daily_workout_items
  ├── game_sessions ── round_results
  ├── skill_scores
  ├── streak_states
  └── achievement_unlocks

games
  ├── challenges
  ├── daily_workout_items
  └── game_sessions

achievements ── achievement_unlocks
```

`game_sessions.daily_workout_item_id` is nullable for free play. `round_results.challenge_id` is nullable for generated Signal Shift rounds whose authoritative configuration is stored in the session snapshot.

## Transaction Boundaries

Use database transactions for:

- Creating a daily workout and both ordered items.
- Appending a round result and advancing the session checkpoint.
- Completing a game session and its workout item.
- Completing a workout, recalculating streak/skills, and inserting unlocks.
- Resetting progress.

Completion services must be idempotent. Unique constraints prevent duplicate daily workouts, duplicate round numbers, and duplicate achievement unlocks.

## Seed and Upgrade Strategy

NativePHP runs migrations on device startup. Use separate, reversible migrations for schema and content:

1. Create definition tables.
2. Create user-state tables.
3. Seed games/challenges/achievements from dedicated content migrations, optionally invoking focused Seeder classes.
4. Add later content through new migrations using stable slugs and idempotent upserts.

Test a fresh database and an existing database containing in-progress sessions before shipping any migration. Never use `migrate:fresh`, destructive replacement, or `db:seed` as an on-device upgrade strategy.

## Data Retention and Reset

- Keep completed sessions and round results until the user explicitly resets progress.
- Keep abandoned sessions for history only if the product surfaces them; otherwise prune them through a documented local policy.
- Preserve seeded definitions during reset.
- Default Reset Progress deletes workouts, workout items, sessions, round results, skill scores, streak state, and unlocks in one transaction.
- Whether display name/preferences are retained must be confirmed with the reset UX; default to retaining preferences and resetting training data.

## Existing Scaffold Tables

The current database also contains `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs`. They are untouched by Prompt 1.

They originate from the Laravel web scaffold and are not part of this v1 product plan. Removal or repurposing requires a later, explicit decision after NativePHP startup, cache, session, and package requirements are verified.

## References

- [NativePHP v4 Databases](https://nativephp.com/docs/mobile/4/digging-deeper/databases)
- [Laravel 13 Migrations](https://laravel.com/docs/13.x/migrations)
- [Laravel 13 Eloquent Relationships](https://laravel.com/docs/13.x/eloquent-relationships)
- [Laravel 13 Database Transactions](https://laravel.com/docs/13.x/database#database-transactions)
- Installed startup source that invokes `migrate --force`: `vendor/nativephp/mobile/resources/xcode/NativePHP/NativePHPApp.swift` and `vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/bridge/LaravelEnvironment.kt`
