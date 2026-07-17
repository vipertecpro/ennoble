# Ennoble Product Specification

## Product Purpose

Ennoble delivers short, interactive daily exercises that train focus, processing speed, precision, visual adaptability, language clarity, concise communication, sentence structure, and critical reading. The first release is a private, single-user experience that operates entirely offline.

All game content, configuration, typography, illustrations, sounds, and motion assets ship with the application. The first release has no account, server, cloud sync, analytics, advertising, payments, or network-dependent core behavior.

## Product Principles

- Sessions should be understandable within seconds and useful in a few minutes.
- Feedback should be immediate, specific, and supportive.
- Difficulty should respond gradually to demonstrated performance.
- Progress should reward consistency without punishing a missed day harshly.
- Data should remain local, inspectable through the product, and resettable by the user.
- Every feature should remain usable with reduced motion and without sound or haptics.

## First-Launch Onboarding

Before entering the main application for the first time, the user completes one native eight-step journey:

1. **Welcome** — Ennoble's identity, a concise product promise, and a single Begin action.
2. **Why Ennoble?** — horizontally paged cards for Focus, Processing Speed, Language, and Daily Growth.
3. **Training Philosophy** — small daily improvements, fully offline operation, privacy, no advertisements, no account, and on-device ownership.
4. **Training Goal** — Improve Focus, Improve Thinking Speed, Improve Communication, Stay Mentally Sharp, or General Improvement.
5. **Difficulty** — Beginner, Intermediate, Advanced, or Adaptive.
6. **Display Name** — an optional, locally stored name with a 40-character maximum.
7. **Accessibility** — theme, sound, haptics, and reduced-motion preferences.
8. **Ready** — a review of the selected profile and settings plus the expected daily training time of 5–10 minutes.

Goal and difficulty are required before continuing. The display name is optional, whitespace-normalized, and never used to create an account. Completing the final step stores all choices and an onboarding-completion timestamp in local SQLite, then enters Home. Incomplete profiles are returned to onboarding on first launch; completed profiles skip it.

Onboarding motion is subtle and communicates progress without carrying required meaning. Reduced Motion removes authored onboarding transitions and animation durations. Screen-reader labels, native text scaling, semantic controls, and the shared minimum touch-target rules remain part of the flow.

## Home Dashboard

Home is the central native overview after onboarding. It adapts to first-use, available, in-progress, completed, returning-user, and empty-history states without fabricating activity. The dashboard contains:

- A device-time greeting with the optional local display name or a friendly fallback.
- A reusable Today workout card with a bounded duration estimated from configured round counts, included skills, selected difficulty, completion percentage, and Start, Continue, or Completed action state.
- Current and longest streak values with an intentional zero-streak explanation.
- A lightweight progress preview containing evidence-backed skill values, seven-day completion, and available per-game personal-best evidence.
- The latest unlocked local achievement or an encouraging empty state.
- Informational previews for Memory Path, Pattern Pulse, Word Forge, and Quick Read.

Opening a Coming Soon preview shows local informational content only. Starting or continuing the Today card opens an honest future workout-flow placeholder; it does not create a session or implement gameplay. Dashboard sections recover independently so one unavailable local preview does not hide unrelated content.

Authored dashboard motion is restrained to section appearance, progress changes, and press feedback. Reduced Motion removes those transforms and durations. Haptics remain optional and preference-gated.

## Main Application Sections

### Today

Today is the default training destination after onboarding. It contains:

- The locally generated daily workout.
- A sequence containing one Signal Shift session and one Clear Thought session.
- An estimated duration based on configured round counts rather than an unreliable countdown promise.
- Workout progress, including current game, completed items, and remaining items.
- A Continue action when an unfinished workout or game session has a valid checkpoint.
- The current daily streak and a concise explanation of what counts as completion.
- A completion celebration that respects reduced-motion settings.
- A daily summary containing accuracy, response time or completion time, score, training time, skill changes, personal bests, and newly unlocked achievements.

One workout is generated per local calendar day. Generation uses bundled games and challenges, the local profile, recent history, and difficulty preference. Reopening Today retrieves the existing workout instead of generating a different sequence.

### Games

The Games library shows:

- **Signal Shift** — playable in the first release.
- **Clear Thought** — playable in the first release.
- A short description, trained skills, difficulty indicator, best score, and recent completion history for each playable game.
- Coming Soon cards for Memory Path, Number Sense, Word Forge, Pattern Pulse, Quick Read, and Sound Recall.

Coming Soon cards open an informational native sheet with the proposed skill area and an explicit unavailable state. They do not navigate to placeholder gameplay or create sessions.

### Progress

Progress presents locally calculated:

- Current and longest streak.
- Total completed workouts.
- Total training time.
- Average accuracy.
- Average response time for compatible rounds.
- Skill scores for the trained focus, speed, precision, adaptability, clarity, structure, and critical-reading areas.
- Seven-day workout activity.
- Per-game personal bests.
- Achievement collection with locked and unlocked states.

Statistics must distinguish unavailable data from zero. A new profile sees an intentional empty state that points to Today rather than fabricated charts.

### Profile

Profile contains:

- Optional local display name.
- Training goal.
- Difficulty preference.
- Sound preference.
- Haptic preference.
- Theme preference: system, light, or dark.
- Reduced-motion preference.
- A destructive Reset Progress flow with a clear confirmation.
- About Ennoble, including the local app version and privacy/offline statement.

Preferences apply locally and immediately where the installed NativePHP APIs permit. Reset Progress removes user-generated training data while restoring bundled content and safe defaults.

## Daily Workout Lifecycle

1. When Today opens, the application loads or creates the workout for the current local date.
2. The workout contains exactly two ordered items in v1: Signal Shift and Clear Thought.
3. Starting an item creates or resumes a game session.
4. Each meaningful answer or round completion writes a transactional checkpoint.
5. Completing a session finalizes its result and advances the workout item.
6. Completing both items finalizes the workout, updates aggregates and streak state, evaluates achievements, and shows the summary.
7. Reopening an incomplete workout resumes from the latest committed state.

Changing the device clock must not silently duplicate a workout for the same stored local date. Exact anti-tampering policy is deferred; v1 should remain deterministic and avoid punitive behavior.

## Signal Shift

### Training Goals

Signal Shift trains focus, processing speed, precision, and visual adaptability.

### Achievable v1 Gameplay

- A native play field displays a small set of geometric targets and distractors.
- Each round presents a clear rule, such as tapping the shape matching a color, form, or position condition.
- The user taps eligible targets before the round timer expires.
- Later rounds change one rule at a time and increase distractor count or reduce response allowance within tested limits.
- The session records correct taps, incorrect taps, missed targets, response time, combo, score, and remaining mistake allowance.
- Haptic and visual feedback identify correct and incorrect actions without relying on color alone.
- A result screen explains accuracy, speed, best combo, score, and skill impact.

The first release does not promise complex physics, continuous high-frame-rate action, multiplayer behavior, or a custom game engine. Any use of canvas, gestures, or native-thread animation must first be verified against the installed v4 source.

### Scoring Direction

Score combines correctness, response speed, and consecutive correct actions. Incorrect taps reset the combo and consume mistake allowance. Accuracy remains the primary quality signal so rapid random tapping cannot outperform careful play.

## Clear Thought

### Training Goals

Clear Thought trains language clarity, concise communication, sentence structure, and critical reading.

### v1 Modes

1. **Remove unnecessary words** — select words that can be removed without changing meaning.
2. **Reorder a sentence** — arrange provided segments into the clearest valid order.
3. **Choose the clearest sentence** — compare locally bundled alternatives and select the strongest version.

Each challenge includes local content, mode, difficulty, accepted answer data, an optional hint, and an educational explanation. Interactions record correctness, hint use, attempts, and completion time. The answer explanation appears after completion, and the result screen summarizes accuracy, time, hints, score, and skill impact.

Content must be editorially reviewed and deterministic. Alternative correct answers are represented explicitly; correctness is never delegated to a remote language model.

## Streaks and Achievements

A daily streak advances only when both workout items are completed. Replaying an individual game may improve personal bests and skill evidence but does not create an additional daily completion.

Achievements use locally evaluated, transparent thresholds such as first workout, multi-day consistency, accuracy milestones, speed milestones, completion without hints, and game-specific mastery. Locked achievements must not reveal misleading progress if the required metric is not yet tracked.

## Originality

Ennoble may draw inspiration from the broad quality bar and daily-training structure of established learning and brain-training products. Its name, brand, writing, questions, illustrations, sound, interface, animation, progression, scoring, and game mechanics must remain original. No competitor asset or exact experience may be reproduced.

## First-Release Exclusions

- Playable Coming Soon games.
- Accounts, authentication, social features, leaderboards, or multiplayer.
- Cloud backup or synchronization.
- Remote content, remote configuration, or AI-generated runtime questions.
- Analytics, advertising, subscriptions, or payments.
- Notifications.
- Complex anti-cheat systems.
