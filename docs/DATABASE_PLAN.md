# Ennoble Database Plan

## Implemented Status

The first-release domain schema was implemented on 2026-07-18. SQLite is the authoritative local store, NativePHP startup migrations are the installation mechanism, and no runtime feature depends on a network connection.

The implementation contains 13 Ennoble tables:

1. `profiles`
2. `settings`
3. `games`
4. `game_levels`
5. `challenges`
6. `daily_workouts`
7. `daily_workout_items`
8. `game_sessions`
9. `game_rounds`
10. `progress_snapshots`
11. `statistics`
12. `achievements`
13. `achievement_unlocks`

The existing Laravel scaffold tables remain unchanged. No account, remote-authentication, queue, or server-owned table was added.

## Storage Rules

- Every core record is stored in local SQLite.
- Foreign keys are enabled and tested.
- Finite states are persisted as stable strings and cast to PHP enums.
- Local workout dates are separate from event timestamps.
- JSON is limited to bounded configuration, accepted answers, response evidence, resume snapshots, accessibility preferences, summaries, and unlock evidence.
- Frequently filtered dates, states, foreign keys, content identifiers, and aggregate scopes are indexed.
- Bundled definitions are inserted by a dedicated migration. `db:seed` is not required on device.
- User-generated history is never overwritten by a bundled-content refresh.

## Implemented Tables

### `profiles`

One local identity row, enforced through unique `singleton_key`.

| Column | Purpose |
| --- | --- |
| `singleton_key` | Stable singleton key, currently `local` |
| `display_name` | Local player name |
| `training_goal` | `TrainingGoal` enum |
| `difficulty_preference` | `Difficulty` enum |
| timestamps | Local auditing |

Profiles are runtime data and are not seeded.

### `settings`

One settings row per profile, enforced by unique `profile_id`.

| Column | Purpose |
| --- | --- |
| `theme_preference` | `system`, `light`, or `dark` |
| `sound_enabled` | Local sound preference |
| `haptics_enabled` | Local haptic preference |
| `reduced_motion` | Reduced-motion preference |
| `daily_reminder_enabled` | Reminder preference only; notifications are not implemented |
| `accessibility_preferences` | Bounded JSON booleans for supported accessibility options |

`ProfileService` creates safe defaults with a profile. `SettingsService` filters unsupported accessibility keys before persistence.

### `games`

Seeded definitions for the two first-release games.

| Column | Purpose |
| --- | --- |
| `type` | Unique `GameType` |
| `slug` | Stable unique content identifier |
| `name`, `description` | Original bundled product copy |
| `status` | `GameStatus` enum |
| `sort_order` | Deterministic workout/library order |
| `skill_keys` | Bounded trained-skill list |
| `configuration` | Versioned game defaults |

The seed migration installs Signal Shift and Clear Thought only.

### `game_levels`

Seeded difficulty definitions owned by a game.

| Column | Purpose |
| --- | --- |
| `difficulty` | `Difficulty` enum |
| `name` | Original level label |
| `round_count` | Default session size |
| `target_response_ms` | Optional compatible response target |
| `configuration` | Bounded game-specific level rules |
| `is_active` | Eligibility for new workouts |

Unique `(game_id, difficulty)` prevents duplicate levels. Three levels are installed for each playable game.

### `challenges`

Versioned bundled Clear Thought content and any future persisted rule definitions.

| Column | Purpose |
| --- | --- |
| `game_id`, `game_level_id` | Owning definition and level |
| `slug` | Stable identifier within a game |
| `mode` | `ClearThoughtMode` enum |
| `content_version` | Payload compatibility version |
| `prompt`, `explanation`, `hint` | Bundled educational content |
| `payload` | Bounded local interaction data |
| `accepted_answers` | Explicit deterministic answers |
| `is_active` | New-session eligibility |

Prompt 2 creates the schema and validator but intentionally does not seed gameplay challenges. Editorial game content belongs to the gameplay implementation prompt.

### `daily_workouts`

One workout per profile and local calendar date.

| Column | Purpose |
| --- | --- |
| `workout_date` | Unique local date per profile |
| `status` | `WorkoutStatus` enum |
| `generation_version` | Deterministic generator version |
| `started_at`, `completed_at` | Lifecycle timestamps |
| `statistics_recorded_at` | Idempotent aggregate marker |
| `training_seconds`, `accuracy` | Queryable final metrics |
| `summary` | Bounded completion summary |

Unique `(profile_id, workout_date)` prevents duplicates caused by reopening Today.

### `daily_workout_items`

The two ordered game assignments inside a workout.

| Column | Purpose |
| --- | --- |
| `game_id`, `game_level_id` | Assigned bundled definitions |
| `position` | Stable one-based order |
| `status` | `WorkoutStatus` enum |
| `configuration` | Generated level snapshot |
| `started_at`, `completed_at` | Item lifecycle |

Unique `(daily_workout_id, position)` and `(daily_workout_id, game_id)` enforce one ordered item per game.

### `game_sessions`

One resumable attempt, optionally linked to a daily item.

| Column | Purpose |
| --- | --- |
| `profile_id`, `game_id`, `game_level_id` | Authoritative ownership and definitions |
| `daily_workout_item_id` | Nullable for later free play |
| `status`, `mode` | Session lifecycle and game mode |
| `snapshot_version`, `current_round`, `state_snapshot` | Bounded resume checkpoint |
| `score`, `accuracy`, `average_response_ms` | Final result metrics |
| result counters | Correct, incorrect, missed, hints, and best combo |
| lifecycle timestamps | Start, interaction, completion, and statistics marker |

The snapshot never duplicates full round history.

### `game_rounds`

Append-only evidence for each meaningful round or answer.

| Column | Purpose |
| --- | --- |
| `game_session_id` | Owning session |
| `challenge_id` | Nullable bundled challenge reference |
| `round_number` | Ordered checkpoint number |
| `outcome` | `RoundOutcome` enum |
| `response_ms`, `score_delta`, `combo`, `hint_used` | Queryable evidence |
| `response` | Bounded interaction/answer evidence |

Unique `(game_session_id, round_number)` prevents duplicate checkpoint writes.

### `progress_snapshots`

Historical skill changes. The newest snapshot per `(profile, skill_key)` is the current value.

| Column | Purpose |
| --- | --- |
| `skill_key` | `SkillKey` enum |
| `score_before`, `score_after`, `delta` | Bounded 0–1000 change |
| `evidence_count` | Number of included evidence events |
| `game_session_id` | Nullable triggering session |
| `recorded_at` | Historical ordering |

Unique `(game_session_id, skill_key)` makes session-backed progress idempotent.

### `statistics`

Rebuildable overall and per-game aggregates.

| Column group | Purpose |
| --- | --- |
| `scope_key`, nullable `game_id` | Unique overall/per-game scope per profile |
| completion totals | Sessions, workouts, and training seconds |
| evidence totals | Correct, attempted, response time, and response count |
| calculated metrics | Accuracy and average response time |
| personal bests | Best score and longest combo |
| streaks | Current, longest, and last completed workout date |
| `last_calculated_at` | Aggregate freshness |

Unique `(profile_id, scope_key)` avoids SQLite nullable-unique ambiguity. `StatisticsService::rebuild()` recreates these rows from completed sessions and workouts.

### `achievements`

Seeded local achievement definitions.

| Column | Purpose |
| --- | --- |
| `slug` | Stable unique identifier |
| `name`, `description` | Original bundled copy |
| `type` | `AchievementType` evaluator key |
| `game_id` | Nullable game-specific scope |
| `criterion` | Bounded threshold JSON |
| `sort_order`, `is_active` | Stable display/evaluation state |

Six transparent first-release definitions are installed.

### `achievement_unlocks`

One unlock per profile and achievement.

| Column | Purpose |
| --- | --- |
| triggering IDs | Nullable session and workout evidence |
| `unlocked_at` | Local unlock time |
| `evidence` | Metric, observed value, and threshold |

Unique `(profile_id, achievement_id)` makes evaluation idempotent.

## Transaction Boundaries

Database transactions protect:

- Singleton profile creation with default settings.
- Daily workout generation with both ordered items.
- Session start and workout-item activation.
- Round append with session checkpoint advancement.
- Session completion, progress snapshots, statistics, and achievement evaluation.
- Workout completion, summary, streak update, and achievement evaluation.
- Statistics rebuilds.

Completion and aggregate markers prevent replayed service calls from counting the same evidence twice.

## Bundled Content and Upgrade Strategy

`2026_07_17_202341_seed_ennoble_content.php` invokes three focused seeders:

- `GameDefinitionSeeder`
- `GameLevelSeeder`
- `AchievementDefinitionSeeder`

They use stable identifiers and SQLite upserts. Re-running them updates bundled definitions without duplicating rows or deleting runtime data. Their migration rollback removes only the exact Prompt 2 definitions.

Verified installed counts:

| Definition | Count |
| --- | ---: |
| Games | 2 |
| Game levels | 6 |
| Achievements | 6 |
| Profiles | 0 |
| Runtime sessions | 0 |

The automated upgrade test creates the previous Laravel scaffold schema, inserts a legacy row, applies only the new product migrations, and confirms that the legacy row and all seeded definitions survive. A separate temporary-SQLite command verified fresh migrate, six-step rollback, and reapply.

## Data Retention and Reset Boundary

Seeded games, levels, challenges, and achievements are durable application content. Profiles, settings, workouts, sessions, rounds, progress, statistics, and unlocks are local runtime data.

The future Reset Progress flow should delete training history, aggregates, and unlocks transactionally while preserving seeded definitions. Profile/settings retention remains a UI/product decision for the later settings prompt; Prompt 2 does not implement reset UI or silently delete any data.

## Existing Scaffold Tables

`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs` remain untouched. They are not used as Ennoble product identity or domain storage.
