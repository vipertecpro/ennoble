# Ennoble Sequential Implementation Plan

## Planning Rules

- Continue from the actual repository state at the start of each stage.
- Use NativePHP Mobile v4 documentation and installed source only.
- Do not begin a stage while its listed blockers remain unresolved.
- Add or update focused Pest tests in every implementation stage.
- Run focused tests, the full PHP suite, formatting, configured static analysis, and relevant NativePHP validation after each stage.
- Device verification is separate from in-process component testing and must be reported honestly.
- Update `IMPLEMENTATION_STATUS.md` and any affected product/architecture/testing document at the end of each stage.

## Stage 1 — Repository Audit and Project Rules

**Objective:** Establish the verified technical/product baseline and permanent development constraints.

**Expected files:** Root `AGENTS.md` and the project documentation in `docs/`.

**Dependencies:** Existing Laravel installation, NativePHP v4 documentation, installed package source/tests.

**NativePHP features:** Version/configuration commands, plugin inventory/validation, component source inventory.

**Tests and checks:** Laravel boot smoke check, existing Pest suite, Pint check, NativePHP debug/version/validate/plugin commands, Composer validation.

**Completion criteria:** Versions, platform state, risks, architecture, schema proposal, component inventory, testing strategy, and future stages are documented without product implementation.

**Out of scope:** Provider registration, dependency changes, native project regeneration, routes, screens, models, migrations, games, and device builds.

## Stage 2 — Database Foundation

**Objective:** Implement the approved local SQLite schema, Eloquent models, enums, factories, and migration-based bundled content foundation.

**Expected files:** `app/Models`, `app/Enums`, `database/migrations`, focused seeders/factories, and database tests.

**Dependencies:** Stage 1 schema review; explicit decision on scaffold-table retention; stable seeded content identifiers.

**NativePHP features:** On-device SQLite and startup migrations.

**Tests required:** Fresh migration, rollback where safe, upgrade from current scaffold database, foreign keys/indexes, model casts/relationships, singleton profile, unique daily workout, idempotent seed migrations, and preservation of user data.

**Completion criteria:** All approved tables migrate on fresh and existing SQLite databases; bundled games and achievement definitions are available without `db:seed`; tests pass.

**Out of scope:** Screens, scoring algorithms, full challenge library, cloud data, or destructive cleanup not separately approved.

## Stage 3 — Design System and Native Application Shell

**Objective:** Preserve the frozen NativePHP compatibility baseline, implement semantic theming, and create the four-tab native shell.

**Expected files:** Theme configuration, icon enums, `routes/mobile.php`, `app/NativeLayouts`, shell NativeComponents/EDGE views, and tests.

**Dependencies:** Frozen provider, plugin, and dependency baseline from Prompt 1.2; final local font/license choice. Do not reopen Composer strategy or edit the Native UI mirror during this stage.

**NativePHP features:** `Route::native`, `NativeLayout`, tab/nav bars, typed icons, theme tokens, safe-area behavior, NativeComponent testing.

**Tests required:** Route registration, tab destinations, active state, navigation/back behavior, light/dark theme values, accessibility audit, compact/large-text source constraints, and platform-conditional assertions.

**Completion criteria:** Today, Games, Progress, and Profile render as honest native empty/foundation screens with working navigation; NativePHP validation passes; both platforms remain marked for device verification until run.

**Out of scope:** Onboarding, game play, fabricated metrics, final illustrations, or WebView screens.

## Stage 4 — Onboarding and Local Profile

**Objective:** Collect the local display name, training goal, difficulty, and preferences without accounts.

**Expected files:** Profile/onboarding NativeComponents and EDGE views, profile service/validation, routes, and tests.

**Dependencies:** Stages 2–3; approved profile fields and defaults.

**NativePHP features:** Native text input, radio/select controls, toggles, model binding, navigation replacement, modal/dialog only where verified.

**Tests required:** First launch, validation, persistence, returning profile, preference changes, skipped optional values, accessibility, disabled/loading/error states, and no network calls.

**Completion criteria:** A fresh install reaches onboarding, saves one local profile, enters Today, and can edit preferences later.

**Out of scope:** Authentication, avatars requiring Camera, cloud profile, social identity, or notifications.

## Stage 5 — Today Dashboard

**Objective:** Generate, display, start, and resume one two-game daily workout.

**Expected files:** Today/workout NativeComponents, EDGE views, `DailyWorkoutGenerator`, workout orchestration service, routes, and tests.

**Dependencies:** Profile, games, challenges, and transactional workout schema.

**NativePHP features:** Native lists/cards, progress presentation, navigation, lifecycle resume, local database.

**Tests required:** One workout per local date, deterministic fallback, ordered two-game items, no duplicate generation, empty/history states, continue/resume selection, completed summary state, accessibility, and offline operation.

**Completion criteria:** Today accurately presents pending, active, resumable, and completed workouts without playable game internals.

**Out of scope:** Full game mechanics, complex calendar tamper detection, notifications, or remote personalization.

## Stage 6 — Games Library

**Objective:** Build the native games catalog with two playable entries and honest Coming Soon states.

**Expected files:** Games NativeComponent/EDGE view, reusable game card, Coming Soon sheet, routes, and tests.

**Dependencies:** Seeded games and shell.

**NativePHP features:** Native list/grid or carousel only after source verification, pressable cards, badges, bottom sheet/modal, native navigation.

**Tests required:** Ordering, playable navigation, Coming Soon non-playability, sheet dismissal, best-score/history empty states, accessibility labels, and small/large layout behavior.

**Completion criteria:** Both real games navigate to their foundations; six Coming Soon cards never create a session.

**Out of scope:** Implementing future games or using placeholder screens that imply playability.

## Stage 7 — Signal Shift Foundation

**Objective:** Deliver a complete, achievable Signal Shift loop with deterministic rounds and persisted results.

**Expected files:** Signal Shift NativeComponents/views, round generator/value objects, scoring/session services, content/configuration, and tests.

**Dependencies:** Stages 2, 3, 5, and 6; verified canvas/shape/pressable performance and events.

**NativePHP features:** Native shapes/canvas or simpler pressables, timers/polling only as documented, native interaction callbacks, vibration, checkpoint persistence.

**Tests required:** Target generation, correct/incorrect/missed input, response timing boundaries, combo, mistake allowance, scoring anti-random-tap behavior, checkpoint resume, completion idempotency, navigation, and accessibility alternatives.

**Completion criteria:** A session can start, survive interruption, complete, and produce a truthful result and persisted round history.

**Out of scope:** Physics engine, continuous high-frame-rate custom rendering, leaderboards, or final polish.

## Stage 8 — Signal Shift Polish

**Objective:** Refine progression, feedback, game identity, results, sound/haptics, and reduced motion.

**Expected files:** Existing Signal Shift services/views/configuration, local assets, and expanded tests.

**Dependencies:** Stable foundation metrics and both-platform performance observations.

**NativePHP features:** Verified native animation/SharedValue behavior, theme-aware styling, device vibration, local audio only if a verified API exists.

**Tests required:** Difficulty bounds, progression evidence, preference gating, reduced-motion behavior, light/dark variants, result/personal-best states, error/restart handling, and render-count safeguards.

**Completion criteria:** Signal Shift is visually coherent, accessible, performant on verified target platforms, and does not rely on unsupported motion.

**Out of scope:** New modes unrelated to the agreed v1 or adding a plugin solely for decorative effects.

## Stage 9 — Clear Thought Foundation

**Objective:** Implement the three Clear Thought modes with local deterministic validation.

**Expected files:** Clear Thought NativeComponents/views, challenge validators, scoring/session services, seeded content migrations, and tests.

**Dependencies:** Database/content foundation and games navigation; editorially reviewed initial question set.

**NativePHP features:** Pressables, chips/buttons, native list/layout, model binding, checkpoint persistence, navigation.

**Tests required:** Unnecessary-word selection, sentence reordering, clearest-sentence choice, accepted alternatives, incorrect answers, hints, explanations, timing, score, resume, content-version compatibility, and completion.

**Completion criteria:** Every mode supports a full offline attempt and result using bundled content.

**Out of scope:** Runtime LLM evaluation, remote content, unrestricted free-text grading, or drag behavior without an accessible alternative.

## Stage 10 — Clear Thought Polish

**Objective:** Refine editorial presentation, difficulty progression, feedback, results, and accessibility.

**Expected files:** Existing Clear Thought views/services/content and expanded tests.

**Dependencies:** Stable validators and reviewed content coverage across difficulties.

**NativePHP features:** Verified motion, selection/reorder patterns, typography, theme, optional haptics.

**Tests required:** Progression boundaries, long text, large text, alternative answers, reduced motion, hints-off path, personal bests, light/dark appearance, and recovery from invalid content.

**Completion criteria:** Clear Thought is clear, polished, deterministic, and usable with screen readers and large type on verified platforms.

**Out of scope:** Additional language games, generative content, or unsupported rich-text editing.

## Stage 11 — Progress and Achievements

**Objective:** Present trustworthy local statistics, seven-day activity, skill scores, personal bests, and achievements.

**Expected files:** Progress/achievement NativeComponents/views, aggregation and evaluator services, queries, and tests.

**Dependencies:** Completed-session evidence from both games and approved achievement definitions.

**NativePHP features:** Lists/grids, progress visuals, badges, sheets/details, native navigation.

**Tests required:** Empty state, aggregates, incompatible timing exclusion, streak display, seven-day boundary, personal bests, idempotent unlocks, locked/unlocked states, rebuild parity, and accessibility.

**Completion criteria:** Every displayed value is derived from persisted evidence and rebuildable; no fake chart data appears.

**Out of scope:** Cross-user comparisons, cloud history, social sharing, or analytics SDKs.

## Stage 12 — Settings and Accessibility

**Objective:** Complete preferences, reset progress, About, reduced motion, and application-wide accessibility hardening.

**Expected files:** Profile/settings/about/reset NativeComponents/views, preference/reset services, shared accessibility helpers where justified, and tests.

**Dependencies:** All major surfaces and confirmed reset-retention policy.

**NativePHP features:** Native controls, dialog/modal/sheet, theme application, typed icons, accessibility audit.

**Tests required:** Preference persistence/application, reset transaction and seeded-content retention, destructive confirmation/cancel, large text, labels/hints, platform variants, sound/haptic gating, and reduced-motion behavior.

**Completion criteria:** All screens pass automated accessibility audits and documented manual checks are ready; reset behavior is safe and explicit.

**Out of scope:** Accounts, privacy upload/export, remote support links required for core use, or notification settings.

## Stage 13 — Complete QA

**Objective:** Audit and fix the integrated v1 against product, architecture, design, persistence, and test contracts.

**Expected files:** Focused fixes, tests, snapshots where valuable, and updated documentation/status.

**Dependencies:** Stages 2–12 complete and target platforms selected for manual verification.

**NativePHP features:** Full route/component testing, plugin/native validation, simulator/device execution by the user, offline behavior.

**Tests required:** Full Pest suite, formatting, static analysis if configured, NativePHP validation, fresh/upgrade database paths, whole-route accessibility sweep, interruption/resume, offline launch, light/dark, reduced motion, compact/large screens, VoiceOver/TalkBack, and performance observation.

**Completion criteria:** No known release-blocking defect; exact automated and manual evidence recorded separately; unresolved platform issues block completion.

**Out of scope:** New features, broad redesign, dependency upgrades unrelated to a verified blocker, or false device claims.

## Stage 14 — Release Documentation and Final Audit

**Objective:** Prepare accurate release guidance and make a final readiness decision.

**Expected files:** README/release documentation only when explicitly approved, status/testing updates, store-content checklist, and final audit report.

**Dependencies:** Complete QA, final identifiers, licensed assets, versioning/signing decisions, and both-platform evidence.

**NativePHP features:** Versioning, credentials, packaging, and platform submission documentation.

**Tests and checks:** Release configuration audit, clean-install/upgrade evidence, signed-build checks performed by the user, privacy/offline review, asset/license inventory, and store metadata review.

**Completion criteria:** Documentation names exact commands and prerequisites, identifiers are consistent, blockers are zero or explicitly accepted, and readiness is stated honestly.

**Out of scope:** Automatically running native build/open/install commands, creating credentials, submitting to stores without explicit authorization, or claiming approval before it occurs.
