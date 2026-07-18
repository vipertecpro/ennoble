# Signal Shift v2 iOS Evidence

These images were captured from Ennoble running natively on the iPhone 17 Simulator with iOS 26.5. The user launched the application with NativePHP watch mode; the Game-UX-1 source was then hot-reloaded and checked against the simulator application directory before capture.

## Capture Matrix

| File | Evidence |
| --- | --- |
| `tutorial-light.png` | First-play practice board with an explicit no-score state |
| `countdown-light.png` | Full-screen countdown owning the viewport |
| `gameplay-light.png` | Advanced live board with minimal HUD and dominant play area |
| `correct-tap-light.png` | Correct-target glow, score burst, and immediate feedback |
| `wrong-tap-light.png` | Wrong target fading away with a physical life removed |
| `combo-light.png` | Transient `x2` combo and floating score during live play |
| `results-light.png` | Completed light-mode session with final scalable result composition |
| `gameplay-dark.png` | Live board using the dark semantic palette |
| `results-dark.png` | Completed dark-mode session with final scalable result composition |

## Manual Session Evidence

- First complete light session: 716 points, 83.3% accuracy, 1,410 ms reaction, `x4` best combo, and 2 of 3 lives held.
- The workout continued through the explicitly non-evidentiary Clear Thought placeholder and completed with the Signal Shift evidence visible on Home.
- A later workout opened Signal Shift without forcing the tutorial and retained the optional Practice Tutorial action.
- Advanced play was exercised for denser five- and six-target waves, short response windows, wrong-target feedback, and a transient combo.
- A second complete dark session reached the final native results state.
- The simulator database was restored to its saved pre-fixture state after the repeated-session and accessibility passes.

## Accessibility Evidence

- Preferred text size was increased four steps in Simulator. This exposed a compressed three-column result layout; the result metrics were changed to a two-plus-one hierarchy and the result remains scrollable to its action at the larger size. Preferred text size was restored afterward.
- The app-level Reduced Motion preference was enabled for a remounted Signal Shift session. Its live device checkpoint recorded `translate_x = 0`, `translate_y = 0`, and `motion_duration = 0` for every stimulus while the motion meaning remained available in labels and direction icons.
- Every live target was exposed as a labeled native button with the current-rule hint. The HUD exposed separate Pause and lives labels.
- Xcode Accessibility Inspector targeted the Ennoble simulator process and completed an audit with no listed warnings. The simulator accessibility order followed title, guidance, score, comparison, metrics, lives, and action.
- Apple documents that VoiceOver itself is unavailable in Simulator. Physical-device VoiceOver and Android TalkBack passes therefore remain release verification, not claims made by this folder.
