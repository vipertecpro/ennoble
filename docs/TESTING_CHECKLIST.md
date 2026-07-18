# Ennoble Testing Checklist

## Current Tooling

| Tool | Installed/current state |
| --- | --- |
| Pest | 4.7.5 |
| PHPUnit | 12.5.30 |
| Laravel Pint | 1.29.3 |
| NativePHP in-process component harness | Present in installed `nativephp/mobile` source |
| Static analysis | Not configured |
| Database under tests | In-memory SQLite from `phpunit.xml` |

Static analysis is not configured. If a tool is approved later, document its configuration and command before making it a completion gate.

## Check Types

### Automated PHP Checks

Run locally and in CI without a simulator:

- Unit tests for pure services, scoring, progression, streaks, achievements, and statistics.
- Feature/database tests for migrations, seed migrations, transactions, model relationships, persistence, and reset behavior.
- NativePHP in-process component tests for rendering, state, interaction, navigation intents, bridge calls, and accessibility.
- Formatter and configured static-analysis checks.
- NativePHP component and plugin validation commands.

These checks do not prove SwiftUI/Compose rendering, native performance, permissions, platform accessibility, or device lifecycle behavior.

### Simulator Checks

Run separately on an Android emulator and iOS Simulator after the user selects a platform and performs the build:

- Launch and cold/warm restart.
- Navigation and back-stack behavior.
- Safe areas, keyboard avoidance, small/large screens, and orientation policy.
- Light/dark appearance.
- Reduced motion.
- Offline launch and core flows.
- Interruption and session resume.
- Native sheets, modals, controls, icons, and haptics where simulators support them.
- Logs for native renderer or bridge failures.

Simulator evidence must include platform, OS/device profile, build type, routes/flows exercised, and failures.

### Physical-Device Checks

Required before release:

- Real touch timing and responsiveness for both games.
- Haptic behavior and preference gating.
- Sound behavior when implemented.
- App background/foreground, process interruption, and resume.
- Storage persistence across app updates.
- Airplane-mode/offline behavior.
- VoiceOver on iOS and TalkBack on Android.
- Large text and display scaling.
- Battery/thermal observations during repeated gameplay.
- Release-build behavior on representative supported devices.

### Manual Visual QA

Review every screen and major state for:

- Product copy and originality.
- Alignment, spacing, clipping, and overflow.
- Initial, active, loading, empty, completed, disabled, and error states.
- Light/dark contrast and non-color state cues.
- Reduced-motion alternatives.
- Small/large screens and large text.
- Meaningful feedback after every action.
- Honest unavailable/Coming Soon behavior.

## Baseline Commands

Run each command independently so one failure does not hide later evidence:

```bash
composer validate --strict
composer install --dry-run --no-interaction --no-scripts
composer audit
composer show nativephp/mobile --locked
composer show nativephp/native-ui --locked
php artisan about --only=environment,drivers
php artisan config:show app.name
php artisan config:show database.default
php artisan config:show nativephp
php artisan route:list --except-vendor
php artisan test --compact
vendor/bin/pint app/Providers/NativeServiceProvider.php config/nativephp.php --format agent
php artisan native:version
php artisan native:debug --json
php artisan native:validate
php artisan native:plugin:list
php artisan native:plugin:validate
git diff --check
```

Do not run a Composer update, including an update dry run, during ordinary feature work. Use the targeted NativePHP dependency-resolution dry run only during an explicitly approved compatibility upgrade following `UPSTREAM_TRACKING.md`.

Use the Laravel Boost absolute-URL resolver before sharing or checking a Herd URL. Record the HTTP status and page title for the smoke check.

Do not run `native:install`, `native:run`, `native:watch`, or `native:open` automatically. Ask which platform the user wants to build or test, then provide only the relevant manual command.

## Prompt QA-1 iOS Simulator Results

Simulator: iPhone 17 Pro, iOS 26.5, Debug build, `com.vipertecpro.ennoble`.

### Manually exercised

- [x] Clean install, bundled-app extraction, migrations, cold launch, and initial onboarding route.
- [x] All six onboarding steps, previous/next, progress, required goal/difficulty, optional display name, completion, and returning launch.
- [x] Keyboard input, Return dismissal, 40-character boundary, and visible primary action while editing.
- [x] System, Light, and Dark selections exercised; Light failed contrast/rendering verification and remains open.
- [x] Sound, haptics, and Reduced Motion controls exercised; physical haptics are not claimed.
- [x] Home greeting, workout card, progress, streak, achievement, and Coming Soon preview layout inspected.
- [x] Games featured content, search, returning search, Focus filter, no-results state, and category chips exercised.
- [x] Workout introduction, countdown, placeholder game, timer, pause request, process relaunch, and persisted resume state exercised.
- [ ] Coming Soon bottom sheet presentation rerun after the direct-host fix.
- [ ] Pause sheet content rerun after the direct-host fix.
- [ ] Resume, Restart, confirmed Exit, transition, completion, and Return Home end to end.
- [ ] Second complete workout cycle.
- [ ] Progress, Profile, Settings, and About screens in the real Simulator.
- [ ] VoiceOver, large Dynamic Type, landscape, and reliable native scrolling/carousel gestures.

### Confirmed QA-1 regressions

- [x] Home/Games/workout loading-error-content overlap reproduced, fixed, covered by negative assertions, and rerun successfully.
- [x] Games hidden sheet content in no-results state reproduced, fixed, covered by negative assertions, and rerun successfully.
- [x] Repeated onboarding radio accessibility labels reproduced, fixed, and rerun successfully.
- [ ] Onboarding safe-area clipping resolved.
- [ ] Explicit Light theme contrast and semantic surface repaint resolved.
- [ ] Presented bottom-sheet/modal child content confirmed after the latest fix.
- [ ] Trailing Games category chip clipping resolved or confirmed scrollable with a reliable gesture.

### QA-1 verification commands

| Command | Result |
| --- | --- |
| `xcrun simctl list devices available` | Exit 0; iPhone 17 Pro on iOS 26.5 selected |
| `xcodebuild -workspace nativephp/ios/NativePHP.xcworkspace -scheme NativePHP-simulator -configuration Debug -sdk iphonesimulator -destination 'platform=iOS Simulator,id=29051E9C-E7F9-40A7-8D50-37427E7BB0B6' -derivedDataPath nativephp/ios/build build` | Exit 0; Debug Simulator app built |
| `xcrun simctl uninstall … com.vipertecpro.ennoble` then `xcrun simctl install … NativePHP-simulator.app` | Exit 0; fresh install completed |
| `xcrun simctl launch --console-pty --terminate-running-process … com.vipertecpro.ennoble` | Exit 0 launch; console attached and runtime initialized |
| `composer validate --strict` | Exit 0; Composer files valid |
| `PAO_DISABLE=1 php artisan test --compact` | Passed: 120 tests, 1,591 assertions |
| `vendor/bin/pint --dirty --format agent` | Exit 0 |
| `php artisan native:validate --no-interaction` | Exit 0; all NativeComponents passed |
| `php artisan native:plugin:validate --no-interaction` | Exit 0; Native UI passed for iOS 18.2 / Android 26 |
| `git diff --check` | Exit 0; no whitespace errors |

Automated accessibility checks do not replace manual VoiceOver validation, and this incomplete run must not be treated as full platform verification.

## Database and Seed Checklist

- [ ] Fresh SQLite migration succeeds.
- [ ] Safe rollback succeeds.
- [ ] Upgrade from the previous application schema succeeds.
- [ ] Upgrade preserves an in-progress workout/session.
- [ ] Foreign keys and unique constraints are active.
- [ ] One profile is created without relying on `db:seed`.
- [ ] Game, challenge, and achievement seed migrations are idempotent.
- [ ] Seed updates preserve historical references and user data.
- [ ] Daily workout uniqueness is enforced.
- [ ] Round-result append and checkpoint update commit atomically.
- [ ] Completion, aggregates, streak, and unlocks commit atomically.
- [ ] Reset Progress removes only approved user-generated data.
- [ ] Seeded content remains available after reset.

Use model factories and specific assertions. Prefer `LazilyRefreshDatabase` when it matches the final suite convention.

## Domain Test Checklist

### Workout Generation

- [ ] Exactly one workout is created for a profile/local date.
- [ ] The workout contains Signal Shift then Clear Thought, or the approved deterministic order.
- [ ] Existing pending/in-progress/completed workout is reused.
- [ ] Difficulty preference and recent evidence influence selection only within bounds.
- [ ] Missing content produces an explicit recoverable failure.
- [ ] Generation requires no network.

### Signal Shift Scoring

- [x] Correct, incorrect, and missed events are distinct in automated domain and native-component tests.
- [x] Accuracy uses the documented denominator.
- [x] Faster valid responses improve only the speed contribution.
- [x] Random rapid taps cannot outperform accurate play.
- [x] Combo, lives, failure, and restart update predictably.
- [x] Score boundaries and rounding are deterministic.
- [x] Checkpoint re-entry preserves the same rule, wave, timer, lives, combo, score, and stimuli.

### Signal Shift Prompt 8 / Game-UX-1 Gameplay

- [x] Exactly three data-driven rounds are configured for Beginner, Intermediate, and Advanced.
- [x] Adaptive resolves to the current Intermediate starting configuration.
- [x] Generated waves are deterministic and contain exactly one eligible target.
- [x] Target color, target shape, excluded shape, movement, size, rotation, speed, density, wave count, and time bounds are covered.
- [x] First-play tutorial and requested tutorial paths create no round evidence.
- [x] Correct taps, wrong taps, misses, timer expiry, combo milestones, lives, round results, failure, and completion are covered.
- [x] Pause, confirmed exit, component re-entry, resume, and restart preserve or clear only the intended local evidence.
- [x] Completion updates score, accuracy, response time, personal best, statistics, progress, and eligible achievements.
- [x] The mixed workout records Signal Shift evidence while Clear Thought remains non-evidentiary.
- [x] Reduced Motion removes authored stimulus movement without changing the rule.
- [x] In-process accessibility audits pass for instructions, tutorial, active play, results, pause, failure, reduced motion, and error states.
- [x] Rebuilt iOS Simulator play-through completed across multiple sessions with tutorial, all rules, correct/wrong/missed outcomes, combo, failure, restart, pause/exit/re-entry, results, mixed workout completion, and persisted Home evidence.
- [x] Light/dark gameplay and results, four-step larger Dynamic Type with result scrolling, app-level Reduced Motion checkpoints, and Accessibility Inspector order/audit verified on iPhone 17 / iOS 26.5.
- [ ] Physical-device VoiceOver and haptic quality, compact-device coverage, Android, and TalkBack verified.
- [ ] Correct/error/round/completion/failure audio verified. The current registered native capability exposes no bundled playback function.

The final Game-UX-1 capture matrix is stored in `docs/screenshots/ios/signal-shift-v2/`. Apple documents that VoiceOver is unavailable in Simulator, so the Accessibility Inspector evidence is not described as a physical VoiceOver pass.

### Clear Thought Scoring

- [ ] All three modes validate correct and incorrect responses.
- [ ] Explicit alternative answers are accepted.
- [ ] Hint use affects only the documented metric.
- [ ] Completion time boundaries are deterministic.
- [ ] Explanations correspond to the completed challenge.
- [ ] Resume preserves selected/reordered state safely.

### Streaks

- [ ] Only a completed two-game workout counts.
- [ ] Same-day replay does not increment the streak.
- [ ] Consecutive local dates increment current streak.
- [ ] A gap resets current but not longest streak.
- [ ] Re-running completion remains idempotent.
- [ ] Date/timezone boundary behavior is covered.

### Achievements

- [ ] Every criterion has positive and negative boundary tests.
- [ ] Unlock insertion is idempotent.
- [ ] Inactive definitions are not newly awarded.
- [ ] Evidence references the triggering session/workout where applicable.
- [ ] Reset removes unlocks but not definitions.

### Statistics

- [ ] Empty data is unavailable rather than zero where appropriate.
- [ ] Averages use compatible completed evidence only.
- [ ] Seven-day activity handles missing days and date boundaries.
- [ ] Personal best tie behavior is defined.
- [ ] Stored aggregates match a full rebuild.

## NativeComponent Checklist

For every implemented screen:

- [ ] Route resolves with `Native::visit()` where applicable.
- [ ] Initial state renders.
- [ ] User actions update expected state.
- [ ] Validation/disabled behavior is covered.
- [ ] Navigation intent and transition are asserted.
- [ ] Resume lifecycle reloads authoritative persisted state.
- [ ] Native bridge calls are faked and asserted when used.
- [ ] Loading, empty, completed, and failure states render where applicable.
- [ ] iOS/Android conditional trees are tested when different.
- [ ] `assertAccessible()` passes or a documented, reviewed exception exists.
- [ ] Icon-only actions have labels.
- [ ] Meaningful images have alt text.
- [ ] Render counts remain reasonable for rapid game interaction.

Useful verified APIs:

- `Native::test()`
- `Native::visit()`
- `Native::fakeBridge()`
- `tap()`, `press()`, `input()`, `toggle()`, `select()`, `call()`
- `emitNative()`
- Navigation and chrome assertions
- `assertAccessible()` and `accessibilityViolations()`
- Platform variants and element assertions

See [NativePHP v4 Testing](https://nativephp.com/docs/mobile/4/testing/introduction).

## Manual Accessibility Checklist

- [ ] VoiceOver reading order and labels verified.
- [ ] TalkBack reading order and labels verified.
- [ ] All actions reachable without relying on a gesture-only path.
- [ ] Correctness and selection are not color-only.
- [ ] Large text does not hide actions or truncate instructions.
- [ ] Screen-reader announcements do not expose decorative icons.
- [ ] Timed interactions provide the approved accessibility behavior.
- [ ] Reduced motion removes non-essential movement.
- [ ] Sound/haptics can be disabled independently.
- [ ] Contrast is checked in light and dark appearances.

## Offline and Lifecycle Checklist

- [ ] Fresh launch succeeds in airplane mode after installation.
- [ ] Onboarding, Today, Games, both games, Progress, and Profile work offline.
- [ ] No fonts, images, sounds, configuration, or content are fetched remotely.
- [ ] Active session survives background/foreground.
- [ ] Latest committed checkpoint survives process termination.
- [ ] Completed session is not completed twice after relaunch.
- [ ] Database migration failure is visible and does not silently reset data.
- [ ] Device clock changes do not create duplicate same-date workouts.

## Prompt 1.1 Readiness Results

| Command/check | Result |
| --- | --- |
| `composer validate --strict` | Exit 0: Composer JSON and lock file are valid with no warnings |
| `composer install --dry-run --no-interaction --no-scripts` | Exit 0: lock contents are installable; nothing to install, update, or remove |
| `composer update nativephp/mobile nativephp/native-ui --with-dependencies --dry-run --no-interaction --no-scripts` | Exit 0: targeted resolution makes no lock or install changes |
| `composer audit` | Exit 0: no security vulnerability advisories found |
| `composer reinstall nativephp/native-ui --no-interaction --no-scripts` | Exit 0: Native UI was removed and mirrored again from `packages/nativephp/native-ui`; subsequent plugin validation passed |
| `composer show nativephp/mobile --locked` | Exit 0: `dev-element` at `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d` |
| `composer show nativephp/native-ui --locked` | Exit 0: `dev-feat/webview-element`, path lock reference `a2c1c943acf70ee1b94599f94c6383e9332bbb2c`, upstream base `ce3d8b760c89dd08e14baad8b05afd82494d3c46` |
| Camera dependency search | No application use found; `nativephp/mobile-camera` removed from Composer, lock data, Boost package guidance, and Camera-specific config examples |
| `php artisan about --only=environment,drivers` | Exit 0: Laravel 13.20.0, PHP 8.4.23, SQLite, local/debug environment |
| `php artisan config:show app.name` | Exit 0: Ennoble |
| `php artisan config:show database.default` | Exit 0: SQLite |
| `php artisan config:show nativephp` | Exit 0: `app_id` is `com.vipertecpro.ennoble`; effective runtime mode is `persistent` |
| `php artisan route:list --except-vendor` | Exit 0: one web `GET /` route; no native routes |
| `php artisan test --compact` | Passed: 2 tests, 2 assertions |
| `vendor/bin/pint --dirty --format agent` | Exit 0: application provider formatted; path-package formatting was restored to its exact upstream form |
| `vendor/bin/pint app/Providers/NativeServiceProvider.php config/nativephp.php --format agent` | Exit 0: targeted application PHP formatting passes |
| `php artisan native:version` | Exit 0: `dev-element` |
| `php artisan native:debug --json` | Exit 0: core plus Native UI detected; local Xcode, Android Studio, Gradle, Java, and CocoaPods tools reported |
| `php artisan native:validate` | Exit 0 with warning: no NativeComponents found |
| `php artisan native:plugin:list` | Exit 0: exactly one registered plugin, `nativephp/native-ui`, with both platform renderers and two bridge functions |
| `php artisan native:plugin:validate` | Exit 0: Native UI passes with Android minimum 26 and iOS minimum 18.2 |
| Herd HTTP smoke check at resolved root URL | Exit 0: HTTP 200, `text/html`, title Ennoble |
| `git diff --check` | Exit 0: no whitespace errors |
| Static analysis | Not run; no tool/configuration exists |
| Android/iOS simulator/device | Not run |

The no-NativeComponents warning is expected until the application-shell prompt and does not change the command's successful exit. These checks do not prove native compilation or device behavior. Re-run the baseline after any dependency, provider, configuration, route, or native-component change.

## Prompt 1.2 Documentation Validation

| Command/check | Result |
| --- | --- |
| Pinned Native UI recursive comparison | Exactly three expected differences: `nativephp.json`, the mirror `README.md`, and the new `UPSTREAM_DIFF.md` |
| Pinned Native UI `nativephp.json` textual diff | Exactly one runtime/source change: `ios.min_version: 18.2` |
| Pinned Native UI `composer.json` byte comparison | Identical to upstream commit `ce3d8b760c89dd08e14baad8b05afd82494d3c46` |
| Forbidden legacy product-name search across Markdown | No matches |
| `composer validate --strict` | Exit 0: `composer.json` and its lock file are valid |
| `php artisan test --compact` | Passed: 2 tests, 2 assertions |
| `vendor/bin/pint app/Providers/NativeServiceProvider.php config/nativephp.php --format agent` | Exit 0: passed |
| `php artisan native:validate --no-interaction` | Exit 0 with the expected warning that no NativeComponents exist yet |
| `php artisan native:plugin:validate --no-interaction` | Exit 0: Native UI passes with Android minimum 26 and iOS minimum 18.2 |
| `git diff --check` | Exit 0: no whitespace errors |
| Android/iOS simulator/device | Not run, as required by Prompt 1.2 |

Prompt 1.2 changed documentation only. It did not update Composer packages, regenerate native projects, or add application code.

## Prompt 3 Native Shell Verification

| Command/check | Result |
| --- | --- |
| Focused Prompt 3 Pest suite | Passed: 26 tests, 300 assertions |
| `php artisan test` | Passed: 67 tests, 448 assertions |
| `composer validate --strict` | Exit 0: Composer JSON and lock file are valid |
| `vendor/bin/pint --dirty --format agent` | Exit 0: application icon enums formatted; frozen mirror unchanged |
| `php artisan route:list` | Exit 0: Splash, Home, Games, Progress, Profile, Settings, and About native routes present |
| `php artisan native:validate --no-interaction` | Exit 0: all seven NativeComponents pass without warnings |
| `php artisan native:plugin:validate --no-interaction` | Exit 0: Native UI passes with Android minimum 26 and iOS minimum 18.2 |
| `git diff --check` | Exit 0: no whitespace errors |
| Static analysis | Not run; no tool/configuration exists |
| Android/iOS simulator/device | Not run, as required by Prompt 3 |

Prompt 3 automated coverage confirms:

- Every registered route resolves with `Native::visit()`.
- Four-tab native chrome exposes labels and active state.
- Splash, Settings, and About navigation intents are correct.
- Shared empty, loading, error, retry, modal, bottom-sheet, inline-loading, and button-loading states render.
- Every placeholder plus the shared-component fixture passes `assertAccessible()`.
- Theme preferences read Prompt 2 settings and resolve system/light/dark palettes.
- Reduced motion resolves reusable durations to zero.
- Haptic, toast, alert, and confirmation bridge calls are faked and asserted.

This evidence is in-process only. Safe areas, SwiftUI/Compose rendering, status bars, light/dark appearance, large text, VoiceOver, TalkBack, offline launch, haptics, and visual balance remain unverified on platforms.

## Prompt Design-2 iOS Simulator Results

### Device

- [x] iPhone 17 Pro simulator used consistently.
- [x] iOS 26.5.
- [x] Final application bundle built with Xcode 26.6 and installed with `simctl`.

### Manual journeys

- [x] Fresh install → six-step onboarding → Home.
- [x] Keyboard shown on display-name input; content and actions remained above the software keyboard.
- [x] Required onboarding selections, back navigation, local persistence, and Reduced Motion control exercised.
- [x] System Light and System Dark repainted all visible semantic surfaces.
- [x] Explicit Light on a dark device appearance and explicit Dark on a light device appearance repainted semantic surfaces and typed content icons consistently.
- [x] Standard extra-extra-extra-large layout rerun after simplifying the practice hero; content remains readable and vertically scrollable.
- [ ] Accessibility-category Dynamic Type above the standard range completed as release QA.
- [x] Native scroll view geometry verified at runtime: finite viewport, larger content size, scrolling enabled, and pan recognizer enabled.
- [ ] Physical-touch scroll and carousel gestures verified. Desktop-injected drags remained unreliable and are not treated as device-touch evidence.
- [x] Home initial state, completed state, greeting, workout card, empty progress/history/achievement states, and relaunch persistence exercised.
- [x] Games search, reset, no-results state, Language and Memory filtering, two-row chip layout, hero content, game cards, and Coming Soon sheet exercised.
- [x] Progress, Profile, Settings, and About placeholder routes exercised without adding restricted features.
- [x] Coming Soon and pause sheets expose their titles and buttons individually.
- [x] Exit modal exposes Close, Keep Training, and Exit to Home individually.
- [x] Portrait, landscape, upside-down rotation handling, and return to upright portrait exercised.
- [x] A complete final-binary placeholder workout cycle executed, in addition to the earlier QA-2 cycles.
- [x] Final workout completion survived process terminate/relaunch.

### Screenshots

- [x] `docs/screenshots/ios/onboarding.png`
- [x] `docs/screenshots/ios/home.png`
- [x] `docs/screenshots/ios/games.png`
- [x] `docs/screenshots/ios/workout-introduction.png`
- [x] `docs/screenshots/ios/workout-countdown.png`
- [x] `docs/screenshots/ios/workout-placeholder.png`
- [x] `docs/screenshots/ios/pause-sheet.png`
- [x] `docs/screenshots/ios/workout-complete.png`
- [x] `docs/screenshots/ios/light-theme.png`
- [x] `docs/screenshots/ios/dark-theme.png`

### Runtime evidence

- [x] No Laravel/PHP error log was produced during the final journey.
- [x] No crash or memory warning was observed.
- [x] Poll-driven render timing was generally 2.2–4.6 ms.
- [ ] SwiftUI publish-during-update warnings eliminated. Recorded as an upstream renderer limitation.
- [ ] Full VoiceOver rotor order verified. Simulator AX labels and focusable children were inspected; a complete VoiceOver session was not performed.
- [ ] TalkBack verified. Android was outside this iOS-only QA prompt.

### Final Prompt Design-2 regression

Run before closing Prompt Design-2:

- [x] `composer validate --strict` — valid.
- [x] `php artisan test --compact` — 127 tests, 1,676 assertions.
- [x] `vendor/bin/pint --dirty --format agent` — passed.
- [x] `php artisan native:validate --no-interaction` — all components passed.
- [x] `php artisan native:plugin:validate --no-interaction` — Native UI passed for Android 26 and iOS 18.2.
- [x] `xcodebuild -workspace nativephp/ios/NativePHP.xcworkspace -scheme NativePHP-simulator ... build` — iOS simulator bundle built successfully.
- [x] `git diff --check` — passed.
