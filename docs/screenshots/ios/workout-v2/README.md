# Workout v2 iOS Evidence

Captured on 2026-07-18 from the iPhone 17 simulator running iOS 26.5.

| Screenshot | Evidence |
| --- | --- |
| `preparation.png` | Focus preparation, ordered workout rhythm, coaching, countdown, and primary action |
| `countdown.png` | Signal Shift full-screen countdown |
| `signal-shift.png` | Active Signal Shift play field |
| `between-games.png` | Persisted Signal Shift coaching, compact evidence, next-game preview, and automatic continuation |
| `workout-celebration.png` | Celebration-first daily completion |
| `todays-progress.png` | Separate analytical step with truthful skill, best-moment, and streak states |
| `return-home.png` | Immediate Home completion card and refreshed Today/streak state |

The run used a reversible copy of the simulator database to expose the full journey. Signal Shift interaction, failure, and replay were exercised in the simulator. The downstream transition reused previously persisted real Signal Shift evidence; Clear Thought remained the explicit non-evidentiary placeholder. The original simulator database was restored after capture.
