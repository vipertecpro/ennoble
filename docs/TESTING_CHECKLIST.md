# Ennoble Testing Checklist

## Current Tooling

| Tool | Installed/current state |
| --- | --- |
| Pest | 4.7.5 |
| PHPUnit | 12.5.30 |
| Laravel Pint | 1.29.3 |
| NativePHP test harness | Present in installed `nativephp/mobile` source |
| Static analysis | Not configured |
| Database under tests | In-memory SQLite from `phpunit.xml` |

Prompt 1 does not add a static-analysis dependency. If one is approved later, document its configuration and command before making it a completion gate.

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
composer validate --strict --no-check-publish
composer show nativephp/mobile --locked
composer show nativephp/native-ui --locked
composer show nativephp/mobile-camera --locked
php artisan about --only=environment,drivers
php artisan config:show app.name
php artisan config:show database.default
php artisan config:show nativephp
php artisan route:list --except-vendor
php artisan test --compact
vendor/bin/pint --test
php artisan native:version
php artisan native:debug --json
php artisan native:validate
php artisan native:plugin:list
php artisan native:plugin:validate
git diff --check
```

Use the Laravel Boost absolute-URL resolver before sharing or checking a Herd URL. Record the HTTP status and page title for the smoke check.

Do not run `native:install`, `native:run`, `native:watch`, or `native:open` automatically. Ask which platform the user wants to build or test, then provide only the relevant manual command.

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

- [ ] Correct, incorrect, and missed events are distinct.
- [ ] Accuracy uses the documented denominator.
- [ ] Faster valid responses improve only the speed contribution.
- [ ] Random rapid taps cannot outperform accurate play.
- [ ] Combo and mistake allowance update predictably.
- [ ] Score boundaries and rounding are deterministic.
- [ ] Resume produces the same final result as uninterrupted play.

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

## Prompt 1 Baseline Results

| Command/check | Result |
| --- | --- |
| `composer validate --strict --no-check-publish` | Exit 1: Composer JSON is valid, with warnings for Native UI `@dev` and Camera `*` unbounded constraints |
| `composer show nativephp/mobile --locked` | Exit 0: `dev-element` at `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d` |
| `composer show nativephp/native-ui --locked` | Exit 0: `dev-feat/webview-element` at `ce3d8b760c89dd08e14baad8b05afd82494d3c46` |
| `composer show nativephp/mobile-camera --locked` | Exit 0: 1.0.3 at `b01139ea47029b6eae695d1a16c41e00f265fd54` |
| `php artisan about --only=environment,drivers` | Exit 0: Laravel 13.20.0, PHP 8.4.23, SQLite, local/debug environment |
| `php artisan config:show app.name` | Exit 0: Ennoble |
| `php artisan config:show database.default` | Exit 0: SQLite |
| `php artisan config:show nativephp` | Exit 0: `app_id` is `com.vipertecpro.ennoble`; effective runtime mode is `persistent` |
| `php artisan route:list --except-vendor` | Exit 0: one web `GET /` route; no native routes |
| `php artisan test --compact` | Passed: 2 tests, 2 assertions |
| `vendor/bin/pint --test` | Passed |
| `php artisan native:version` | Exit 0: `dev-element` |
| `php artisan native:debug --json` | Exit 0: package/runtime versions and local Xcode, Android Studio, Gradle, Java, and CocoaPods tools detected |
| `php artisan native:validate` | Exit 0 with warning: no NativeComponents found |
| `php artisan native:plugin:list` | Exit 0 but reports an error-level readiness condition: provider unpublished; Native UI and Camera unregistered |
| `php artisan native:plugin:validate` | Exit 1: Camera passed; Native UI failed because `ios.min_version` is missing |
| Herd HTTP smoke check at resolved root URL | Exit 0: HTTP 200, `text/html`, title Ennoble |
| `git diff --check` | Exit 0: no whitespace errors |
| Static analysis | Not run; no tool/configuration exists |
| Android/iOS simulator/device | Not run |

These results describe Prompt 1 only. Re-run the baseline after any dependency, provider, configuration, route, or native-component change.
