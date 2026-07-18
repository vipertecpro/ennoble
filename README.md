# Ennoble

Ennoble is an open-source, offline-first native brain-training application built with Laravel, NativePHP Mobile v4, SuperNative, EDGE components, and local SQLite.

Application development guidance lives in [AGENTS.md](AGENTS.md). Contributors should begin with [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md).

## NativePHP Mobile v4 Status

Ennoble currently targets NativePHP Mobile v4 Beta and intentionally uses official NativePHP packages.

The required NativePHP Mobile development branch and the later Native UI package line are temporarily incompatible through Composer. To keep the dependency baseline reproducible without editing installed dependencies or downgrading NativePHP Mobile, the repository contains a narrowly scoped mirror at `packages/nativephp/native-ui`.

This is a transparent compatibility decision, not an application fork. Ennoble product code must remain outside the mirror. The mirror will be removed when NativePHP publishes compatible official core and Native UI packages.

See [docs/UPSTREAM_TRACKING.md](docs/UPSTREAM_TRACKING.md) for the pinned branches, commits, exact differences, and permanent upgrade checklist.

## iOS UI Foundation

The current offline onboarding, Home, Games, and placeholder workout foundation has been exercised on an iPhone 17 Pro simulator running iOS 26.5. The full QA record and remaining NativePHP limitations are documented in [docs/IMPLEMENTATION_STATUS.md](docs/IMPLEMENTATION_STATUS.md).

| Home | Games | Workout pause |
| --- | --- | --- |
| ![Ennoble Home](docs/screenshots/ios/home.png) | ![Ennoble Games](docs/screenshots/ios/games.png) | ![Ennoble workout pause sheet](docs/screenshots/ios/pause-sheet.png) |
