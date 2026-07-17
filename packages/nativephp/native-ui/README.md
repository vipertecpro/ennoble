# Temporary NativePHP Native UI Mirror

> **WARNING**
>
> **THIS DIRECTORY EXISTS ONLY UNTIL THE OFFICIAL PACKAGES BECOME COMPATIBLE.**
>
> Do not place Ennoble application code here. Do not fix Ennoble product bugs here. Do not modify this mirror unless synchronising it with a reviewed upstream Native UI commit.

This directory is a temporary, project-owned mirror of the official NativePHP Native UI package.

## Why the Mirror Exists

Ennoble currently requires:

- NativePHP Mobile branch `element`, installed as `dev-element` at commit `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d`.
- Native UI branch `feat/webview-element`, exposed as `dev-feat/webview-element` and based on commit `ce3d8b760c89dd08e14baad8b05afd82494d3c46`.

The official Native UI manifest fix was later published on a branch that requires `nativephp/mobile ^4.0`. Composer cannot satisfy that constraint with the required `dev-element` branch because the core branch has no compatible official alias.

Ennoble therefore installs this directory through a root Composer path repository. This avoids editing `vendor/`, inventing a branch alias, or downgrading the required NativePHP core.

## Upstream Source

- Repository: `https://github.com/NativePHP/mobile-ui`
- Mirrored branch: `feat/webview-element`
- Mirrored commit: `ce3d8b760c89dd08e14baad8b05afd82494d3c46`
- Official manifest-fix commit: `48c1726f4ac17b219072a31c24aaefedc1ab8efa`

## Local Modifications

Relative to the mirrored commit:

- `nativephp.json` adds `ios.min_version: 18.2`.
- `README.md` is this Ennoble-specific warning and maintenance guide.
- `UPSTREAM_DIFF.md` records the exact audit boundary.

No PHP, Swift, Kotlin, renderer, configuration, or test implementation is locally changed. The package's `composer.json` is also unchanged from the pinned commit; its `nativephp/mobile: *` constraint is inherited from that source. The temporary repository and synthetic version mapping are declared in Ennoble's root `composer.json`.

See `UPSTREAM_DIFF.md` for the auditable file-by-file comparison.

## Maintenance Rules

- Never implement Ennoble domain logic, scoring, workouts, persistence, screens, or product components in this directory.
- Never use the mirror to conceal an application bug.
- Fix application behavior in Ennoble first.
- Change the mirror only to synchronise with a reviewed upstream commit.
- Re-run the upstream diff after every synchronisation.
- Document every changed or added file in `UPSTREAM_DIFF.md`.
- Keep plugin registration in `app/Providers/NativeServiceProvider.php`; do not add application registration logic here.

Other Markdown files shipped by the pinned upstream commit remain upstream package material. They are not Ennoble contributor instructions and must not be edited independently.

## Removal Procedure

When official NativePHP Mobile v4 and Native UI packages resolve together through supported Composer constraints:

1. Review the official release notes and upgrade guide.
2. Confirm the official packages contain the required EDGE functionality and iOS manifest metadata.
3. Replace the root path dependency with the official Native UI package constraint.
4. Remove the root `ui` path repository and synthetic version mapping.
5. Remove this entire directory.
6. Update `docs/UPSTREAM_TRACKING.md`, `docs/CONTRIBUTING.md`, the root README, and implementation status.
7. Run strict Composer validation, targeted dependency resolution, the complete test suite, NativePHP validation, and `git diff --check`.
8. Build and verify Android and iOS before claiming platform compatibility.

Until then, this mirror is frozen infrastructure.
