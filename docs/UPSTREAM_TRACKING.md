# Ennoble Upstream Tracking

This document records the temporary NativePHP compatibility boundary. It is an engineering ledger, not permission to update dependencies during ordinary feature work.

## NativePHP Mobile

| Field | Current state |
| --- | --- |
| Repository | `https://github.com/nativephp/mobile-air` |
| Branch | `element` |
| Composer version | `dev-element` |
| Locked commit | `c959c20f27c4430ad6d74e586c6b1bd0b5bbb59d` |
| Expected stable release | NativePHP Mobile v4 stable; no release date or final constraint is recorded, so contributors must not invent one |

### Known Compatibility Notes

- The required SuperNative and EDGE implementation is supplied by the `element` development branch.
- The branch does not currently advertise a Composer alias that satisfies Native UI's later `nativephp/mobile ^4.0` constraint.
- NativePHP Mobile v4 is pre-release software at this baseline. Documentation, installed source, and package tests must be checked before adopting changed APIs.
- The package is installed through the root VCS repository declaration. Do not move the branch or commit during unrelated feature work.

### Removal Checklist

- [ ] An official NativePHP Mobile v4 package exposes the required SuperNative and EDGE feature set.
- [ ] Its supported Composer constraint resolves with the official Native UI package.
- [ ] Release notes and the v4 upgrade guide have been reviewed.
- [ ] A targeted Composer dry run reports no unrelated dependency changes.
- [ ] The locked version and source commit have been recorded here.

## Native UI

| Field | Current state |
| --- | --- |
| Repository | `https://github.com/NativePHP/mobile-ui` |
| Upstream branch | `feat/webview-element` |
| Composer version | `dev-feat/webview-element` |
| Mirrored commit | `ce3d8b760c89dd08e14baad8b05afd82494d3c46` |
| Path lock reference | `a2c1c943acf70ee1b94599f94c6383e9332bbb2c` |
| Local path | `packages/nativephp/native-ui` |

### Current Workaround

Root `composer.json` declares `packages/nativephp/native-ui` as a path repository, maps it to `dev-feat/webview-element`, disables symlinking, and requires that explicit development version. This keeps installation reproducible and avoids a direct `vendor/` edit.

The pinned package's own `composer.json` is unchanged from the mirrored commit and requires `nativephp/mobile: *`. The later upstream package line changes that constraint to `^4.0`, which does not resolve against the required `dev-element` branch without an official alias. Ennoble must not invent that alias.

### Manifest Changes

`nativephp.json` is the only runtime/source file changed relative to the mirrored commit. Ennoble adds:

```json
"ios": {
    "min_version": 18.2
}
```

The value is the official fix from NativePHP/mobile-ui commit `48c1726f4ac17b219072a31c24aaefedc1ab8efa` and matches the installed NativePHP v4 Xcode deployment target.

The package-local `README.md` and `UPSTREAM_DIFF.md` are Ennoble maintenance documents. They do not change package behavior.

### Removal Checklist

- [ ] An official Native UI package includes a valid iOS minimum version.
- [ ] Its Composer constraint resolves against the selected official NativePHP Mobile v4 package.
- [ ] The official package contains every EDGE component Ennoble already uses.
- [ ] Root `composer.json` is changed from the path package to the official package constraint.
- [ ] The root `ui` path repository and version mapping are removed.
- [ ] `packages/nativephp/native-ui` is removed.
- [ ] `composer.lock` records the official source and distribution references.
- [ ] Plugin registration still lists exactly one Native UI provider.
- [ ] Plugin and application validation pass.

## SuperNative

| Field | Current state |
| --- | --- |
| Version | No independent Composer package or version; the current feature surface comes from `nativephp/mobile dev-element` plus `nativephp/native-ui dev-feat/webview-element` |
| Android renderer status | Native UI Kotlin renderers are present; plugin validation passes |
| iOS renderer status | Native UI Swift renderers are present; plugin validation passes with iOS 18.2 |
| Application usage | No Ennoble `NativeComponent`, native route, or product screen exists yet |
| Platform build status | Unverified; neither Android nor iOS was built for this compatibility baseline |

### Known Roadmap Items

These are Ennoble readiness items, not promises about the upstream roadmap:

- Adopt compatible official NativePHP Mobile v4 and Native UI packages when published.
- Verify each EDGE component actually used by Ennoble against installed source and v4 documentation.
- Add in-process component coverage as screens are implemented.
- Perform Android and iOS rendering, accessibility, and lifecycle verification before release.

### Breaking-Change Considerations

- v4 development branches may change element names, properties, events, routing, layouts, validation, or renderer behavior.
- A passing plugin validator does not prove SwiftUI or Jetpack Compose compilation.
- Never update one side of the core/UI pair without resolving and validating the other.
- Recheck application routes, component tests, accessibility, themes, navigation chrome, native events, and persistence after an upstream change.
- Treat generated native projects as replaceable output and do not hand-maintain compatibility fixes inside them.

## Permanent Upgrade Checklist

Use this procedure only for an approved NativePHP compatibility upgrade:

1. Read the official release notes.
2. Read the matching NativePHP Mobile v4 upgrade guide.
3. Confirm the official core and Native UI versions are mutually compatible.
4. Remove the local compatibility layer.
5. Restore the official Composer dependency and supported version constraint.
6. Remove the root path repository and synthetic version mapping.
7. Remove or rewrite temporary compatibility documentation so it records the completed migration rather than active instructions.
8. Run strict Composer validation, a targeted dependency-resolution dry run, security audit, and NativePHP validation.
9. Run focused tests and the complete PHP test suite.
10. Build and verify Android manually.
11. Build and verify iOS manually.
12. Verify every EDGE component used by Ennoble on both platforms, including interaction, accessibility, light/dark appearance, large text, and reduced motion.

Do not describe an upgrade as complete until automated checks pass and both platform builds have been performed and recorded.
