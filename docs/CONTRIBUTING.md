# Contributing to Ennoble

## Project Philosophy

Ennoble is an open-source, offline-first brain-training application built with Laravel, NativePHP Mobile v4, SuperNative, and native EDGE components. Core experiences must work without a network connection, and required content and assets must ship with the application.

The project avoids unnecessary dependencies and prioritises readable, maintainable code over clever abstractions. Native UI, local SQLite persistence, explicit domain services, and meaningful automated tests are the default.

## Contributor Expectations

- Read and follow the root `AGENTS.md` before changing the repository.
- Follow official Laravel conventions and the official documentation for the installed Laravel version.
- Use only the official NativePHP Mobile v4 documentation and verify unresolved behavior against installed package source.
- Preserve Ennoble's offline-first architecture.
- Prefer readability and direct code over speculative abstraction.
- Keep business rules in focused domain services.
- Keep `NativeComponent` classes focused on screen state, interaction, navigation, and native UI events.
- Never duplicate scoring logic across components, controllers, or views.
- Never duplicate workout generation or completion logic.
- Keep commits focused on one coherent change.
- Add or update meaningful Pest tests with every feature.
- Report in-process, simulator, and physical-device verification separately and honestly.

## Pull Request Expectations

- Keep one feature or infrastructure concern per pull request.
- Update the relevant documentation whenever behavior or architecture changes.
- Explain non-obvious architectural decisions and their tradeoffs.
- Avoid unrelated refactoring, formatting churn, or dependency updates.
- Preserve existing user work and call out any unavoidable overlap.
- Run all applicable commands in `docs/TESTING_CHECKLIST.md` and report exact results.
- Do not claim Android, iOS, accessibility, offline, or visual verification unless it was actually performed.

## Temporary NativePHP Compatibility Layer

Ennoble currently contains a temporary mirror of the official Native UI package at `packages/nativephp/native-ui`.

### Why It Exists

The application requires NativePHP Mobile's `dev-element` branch, currently locked at commit `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d`. Native UI is based on upstream branch `feat/webview-element` at commit `ce3d8b760c89dd08e14baad8b05afd82494d3c46` and is exposed to Composer as `dev-feat/webview-element`.

The later official Native UI manifest fix requires `nativephp/mobile ^4.0`, but the required `dev-element` core branch has no compatible Composer branch alias. The project therefore uses a root Composer path repository and an explicit version mapping so the pinned package can be installed without editing `vendor/` or downgrading the core branch.

### Exact Local Differences

Relative to upstream commit `ce3d8b760c89dd08e14baad8b05afd82494d3c46`:

- `nativephp.json` adds `ios.min_version: 18.2`. This value comes from the official fix at NativePHP/mobile-ui commit `48c1726f4ac17b219072a31c24aaefedc1ab8efa`.
- `README.md` is replaced with an Ennoble-specific warning and maintenance guide.
- `UPSTREAM_DIFF.md` is an Ennoble-only audit record.

No PHP, Swift, Kotlin, configuration, test, or renderer implementation differs from the pinned upstream commit. The mirrored package's `composer.json` also matches that commit and retains its original `nativephp/mobile: *` requirement. The temporary path repository and version mapping live in Ennoble's root `composer.json`.

See `docs/UPSTREAM_TRACKING.md` and `packages/nativephp/native-ui/UPSTREAM_DIFF.md` for the complete compatibility record.

### Contributor Guardrail

Never implement application code inside `packages/nativephp/native-ui`.

Do not fix Ennoble bugs, add product components, change renderer behavior, or place domain logic in the mirror. Application issues belong in Ennoble. A mirror change is allowed only when synchronising to a reviewed upstream Native UI commit, and every resulting difference must be recorded in `UPSTREAM_DIFF.md`.

### Removal Procedure

Remove the mirror when official NativePHP Mobile and Native UI packages provide a compatible Composer dependency pair for the required v4 feature set:

1. Read the official NativePHP Mobile v4 release notes and upgrade guide.
2. Confirm the official core and Native UI constraints resolve together without aliases invented by Ennoble.
3. Record the new package versions and commits in `docs/UPSTREAM_TRACKING.md`.
4. Replace the root Native UI path dependency with the official package constraint.
5. Remove the `ui` path repository and its synthetic version mapping from root `composer.json`.
6. Remove `packages/nativephp/native-ui`.
7. Update compatibility references in the README and project documentation.
8. Run a targeted Composer update for NativePHP Mobile and Native UI only.
9. Run the complete automated validation checklist.
10. Build and verify Android and iOS manually before describing the upgrade as platform-verified.

Until those conditions are met, the dependency strategy, mirror, and plugin registration are frozen infrastructure.
