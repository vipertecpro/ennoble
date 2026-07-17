# Native UI Upstream Difference Record

## Comparison Baseline

| Field | Value |
| --- | --- |
| Upstream repository | `https://github.com/NativePHP/mobile-ui` |
| Upstream branch | `feat/webview-element` |
| Mirrored commit | `ce3d8b760c89dd08e14baad8b05afd82494d3c46` |
| Official manifest-fix commit | `48c1726f4ac17b219072a31c24aaefedc1ab8efa` |
| Local package version | `dev-feat/webview-element` |

## File Differences

| File | Difference from mirrored commit | Reason |
| --- | --- | --- |
| `nativephp.json` | Adds `ios.min_version: 18.2` | NativePHP plugin validation requires the minimum. The value comes from the official upstream fix and matches the installed v4 Xcode deployment target. |
| `README.md` | Replaces the upstream package README with an Ennoble compatibility warning and removal guide | Prevents contributors from treating the mirror as application-owned implementation space. Documentation only. |
| `UPSTREAM_DIFF.md` | New file | Makes the compatibility boundary auditable. Documentation only. |

No other file differs from the pinned upstream commit. In particular, no PHP, Swift, Kotlin, renderer, configuration, workflow, or test implementation is locally modified.

## Composer Compatibility Record

| File | Status | Reason |
| --- | --- | --- |
| `packages/nativephp/native-ui/composer.json` | Identical to the mirrored commit; retains the upstream commit's `nativephp/mobile: *` constraint | This is not an Ennoble-authored package modification. A later upstream line uses `nativephp/mobile ^4.0`, which does not resolve against the required `dev-element` branch. |
| `/composer.json` | Declares the `ui` path repository, maps the package to `dev-feat/webview-element`, disables symlinking, and requires that explicit version | This is the temporary application-level compatibility layer. |
| `/composer.lock` | Records the path distribution and reference `a2c1c943acf70ee1b94599f94c6383e9332bbb2c` | Makes installations reproducible from the project-owned mirror. |

## Audit Procedure

After an approved upstream synchronisation:

1. Download the exact upstream commit into a temporary directory.
2. Compare that directory recursively with `packages/nativephp/native-ui`.
3. Confirm that only the files documented above differ.
4. Inspect each textual diff rather than relying only on filenames.
5. Run Composer, NativePHP plugin, test, and whitespace validation.
6. Update the baseline commit and every changed-file entry in this document.

Any undocumented difference blocks the synchronisation from being accepted.
