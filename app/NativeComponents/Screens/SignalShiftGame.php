<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\GameSessionService;
use App\Domain\Games\SignalShift\SignalShiftGameService;
use App\Domain\Games\SignalShift\SignalShiftRule;
use App\Domain\Games\SignalShift\SignalShiftRuleEngine;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\GameType;
use App\Enums\SessionStatus;
use App\Models\GameSession;
use App\NativeUI\Dialogs\InteractsWithDialogs;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

final class SignalShiftGame extends NativeComponent
{
    use InteractsWithDialogs;

    public string $screenState = 'content';

    public string $errorMessage = 'Signal Shift could not restore this checkpoint. Your completed evidence remains safe.';

    public ?int $sessionId = null;

    public string $phase = 'instructions';

    public string $difficulty = '';

    public bool $tutorialRequired = true;

    public bool $tutorialComplete = false;

    public string $tutorialFeedback = 'Find the one shape that matches the rule.';

    public int $gameRound = 0;

    public int $totalRounds = 3;

    public int $wave = 0;

    public int $waveCount = 0;

    public int $roundSecondsRemaining = 0;

    public int $waveSecondsRemaining = 0;

    public int $elapsedSeconds = 0;

    public int $maxLives = 3;

    public int $lives = 3;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $comboMilestone = 4;

    public int $score = 0;

    public float $progress = 0.0;

    public string $ruleText = '';

    /**
     * @var list<array<string, mixed>>
     */
    public array $stimuli = [];

    /**
     * @var list<string>
     */
    public array $resolvedStimulusIds = [];

    public int $waveStartedAtMs = 0;

    public string $feedbackMessage = 'Stay with the rule.';

    public string $feedbackTone = 'neutral';

    public string $roundAccuracy = 'Not recorded';

    public string $roundReactionTime = 'Not recorded';

    public string $roundScore = '0';

    public string $roundLives = '3 of 3';

    public string $bestScoreComparison = 'Complete the round to set your pace.';

    public string $motivationalMessage = 'Settle into the rhythm and respond with intention.';

    public ?int $previousBestScore = null;

    public bool $newPersonalBest = false;

    public bool $paused = false;

    public bool $isSubmitting = false;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public int $feedbackMotionDuration = 0;

    public int $countdownMotionDuration = 0;

    public int $roundCountdown = 3;

    public string $floatingScore = '';

    public int $feedbackSerial = 0;

    public int $feedbackTicksRemaining = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->sessionId = (int) $this->param('session');
        $this->loadSession();
    }

    public function render(): Element
    {
        return $this->view('screens.signal-shift-game');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->hidden();
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    public function beginSignalShift(): void
    {
        if ($this->tutorialRequired) {
            $this->startTutorial();

            return;
        }

        $this->prepareRound(1);
    }

    public function practiceTutorial(): void
    {
        $this->tutorialRequired = true;
        $this->startTutorial();
    }

    public function skipTutorial(): void
    {
        $this->tutorialComplete = true;
        $this->prepareRound(1);
    }

    public function tapTutorialStimulus(string $stimulusId): void
    {
        if ($this->phase !== 'tutorial' || $this->tutorialComplete) {
            return;
        }

        $stimulus = $this->findStimulus($stimulusId);

        if ($stimulus === null) {
            return;
        }

        if ((bool) $stimulus['is_target']) {
            $this->tutorialComplete = true;
            $this->tutorialFeedback = 'That’s it. Shape and color both matched.';
            $this->feedbackTone = 'success';
            $this->resolvedStimulusIds[] = $stimulusId;
            $this->storeCheckpoint();
            app(HapticService::class)->trigger(HapticFeedback::Selection);

            return;
        }

        $this->tutorialFeedback = 'Not this one. Check both the color and the shape.';
        $this->feedbackTone = 'danger';
        $this->storeCheckpoint();
        app(HapticService::class)->trigger(HapticFeedback::Error);
    }

    public function startRound(): void
    {
        if ($this->phase !== 'round_intro' || $this->gameRound < 1) {
            return;
        }

        $this->phase = 'round_countdown';
        $this->roundCountdown = 3;
        $this->feedbackMessage = 'Set your focus.';
        $this->feedbackTone = 'neutral';
        $this->storeCheckpoint();
        app(HapticService::class)->trigger(HapticFeedback::Impact);
    }

    #[Poll(1000)]
    public function tickGame(): void
    {
        if ($this->phase === 'round_countdown') {
            $this->tickRoundCountdown();

            return;
        }

        if ($this->phase !== 'playing' || $this->paused || $this->isSubmitting) {
            return;
        }

        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::InProgress) {
            return;
        }

        if ($this->feedbackTicksRemaining > 0) {
            $this->feedbackTicksRemaining--;

            if ($this->feedbackTicksRemaining === 0) {
                $this->floatingScore = '';
                $this->feedbackTone = 'neutral';
                $this->feedbackMessage = 'Find the signal.';
            }
        }

        $this->elapsedSeconds++;
        $this->roundSecondsRemaining = max(0, $this->roundSecondsRemaining - 1);
        $this->waveSecondsRemaining = max(0, $this->waveSecondsRemaining - 1);

        if ($this->waveSecondsRemaining === 0) {
            $this->expireWave();

            return;
        }

        $this->storeCheckpoint();
    }

    public function tapStimulus(string $stimulusId): void
    {
        if ($this->phase !== 'playing'
            || $this->paused
            || in_array($stimulusId, $this->resolvedStimulusIds, true)) {
            return;
        }

        $session = $this->session();
        $stimulus = $this->findStimulus($stimulusId);

        if ($session === null || $stimulus === null || $session->status !== SessionStatus::InProgress) {
            $this->screenState = 'error';

            return;
        }

        $isTarget = (bool) $stimulus['is_target'];
        $previousScore = $this->score;
        $responseMs = max(1, $this->currentMilliseconds() - $this->waveStartedAtMs);
        $this->resolvedStimulusIds[] = $stimulusId;

        if ($isTarget) {
            $this->combo++;
            $this->bestCombo = max($this->bestCombo, $this->combo);
            $this->feedbackMessage = $this->combo > 1
                ? 'Correct. Combo '.$this->combo.'.'
                : 'Correct. Attention held.';
            $this->feedbackTone = 'success';
        } else {
            $this->lives = max(0, $this->lives - 1);
            $this->combo = 0;
            $this->feedbackMessage = 'That shape did not match. Re-read the rule.';
            $this->feedbackTone = 'danger';
        }

        try {
            app(SignalShiftGameService::class)->recordTap(
                session: $session,
                stimulus: $stimulus,
                responseMs: $responseMs,
                combo: $this->combo,
                gameRound: $this->gameRound,
                wave: $this->wave,
                stateSnapshot: $this->snapshot(),
            );
            $this->refreshLiveScore($session);
            $this->feedbackSerial++;
            $this->feedbackTicksRemaining = 1;
            $this->floatingScore = $isTarget && $this->score > $previousScore
                ? '+'.$this->formatNumber($this->score - $previousScore)
                : '';

            app(HapticService::class)->trigger(
                $isTarget ? HapticFeedback::Selection : HapticFeedback::Error,
            );

            if ($isTarget && $this->combo > 0 && $this->combo % $this->comboMilestone === 0) {
                app(HapticService::class)->trigger(HapticFeedback::Impact);
            }

            if ($this->lives === 0) {
                $this->failRound();

                return;
            }

            if ($isTarget) {
                $this->advanceWaveOrFinishRound();
            } else {
                $this->storeCheckpoint();
            }
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    public function continueAfterRound(): void
    {
        if ($this->phase !== 'round_result') {
            return;
        }

        if ($this->gameRound < $this->totalRounds) {
            $this->prepareRound($this->gameRound + 1);

            return;
        }

        $this->completeGame();
    }

    public function pauseWorkout(): void
    {
        if ($this->phase !== 'playing' || $this->isSubmitting) {
            return;
        }

        $this->paused = true;
        $this->bottomSheetVisible = true;
        $this->storeCheckpoint();
        app(HapticService::class)->trigger(HapticFeedback::Impact);
    }

    public function resumeWorkout(): void
    {
        $this->paused = false;
        $this->bottomSheetVisible = false;
        $this->waveStartedAtMs = $this->currentMilliseconds();
        $this->storeCheckpoint();
    }

    public function requestExit(): void
    {
        $this->paused = true;
        $this->bottomSheetVisible = false;
        $this->dialogVisible = true;
        $this->storeCheckpoint();
        app(HapticService::class)->trigger(HapticFeedback::Warning);
    }

    public function cancelExit(): void
    {
        $this->dialogVisible = false;
        $this->paused = false;
        $this->waveStartedAtMs = $this->currentMilliseconds();
        $this->storeCheckpoint();
    }

    public function confirmExit(): void
    {
        $this->dialogVisible = false;
        $this->paused = true;
        $this->storeCheckpoint();
        $this->replace('/')->transition($this->screenTransition());
    }

    public function restartGame(): void
    {
        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::InProgress) {
            $this->screenState = 'error';

            return;
        }

        try {
            $this->resetRuntimeState();
            app(SignalShiftGameService::class)->restart($session, $this->snapshot());
            $this->bottomSheetVisible = false;
            $this->dialogVisible = false;
            app(HapticService::class)->trigger(HapticFeedback::Warning);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    public function continueWorkout(): void
    {
        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::Completed) {
            $this->screenState = 'error';

            return;
        }

        $this->replace(
            $this->route('native.workout.transition', [
                'item' => $session->workoutItem->getKey(),
            ]),
        )->transition($this->screenTransition());
    }

    public function returnToWorkout(): void
    {
        $this->replace('/workout')->transition($this->screenTransition());
    }

    public function onBackPressed(): void
    {
        if ($this->phase === 'game_result') {
            $this->continueWorkout();

            return;
        }

        $this->requestExit();
    }

    private function loadSession(): void
    {
        try {
            $profile = app(ProfileService::class)->current();
            $session = $this->session();

            if ($profile === null
                || $session === null
                || $session->game->type !== GameType::SignalShift
                || $session->isFrameworkPlaceholder()) {
                $this->screenState = 'error';

                return;
            }

            if ($session->status === SessionStatus::Completed) {
                $this->replace(
                    $this->route('native.workout.transition', [
                        'item' => $session->workoutItem->getKey(),
                    ]),
                )->transition(Transition::None);

                return;
            }

            if (! (bool) data_get($session->state_snapshot, 'prepared', false)) {
                $this->replace(
                    $this->route('native.workout.preparation', ['session' => $session->getKey()]),
                )->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $configuration = $session->level->configuration;

            app(SignalShiftRuleEngine::class)->rulesFor($session->level);

            $this->difficulty = $session->level->difficulty->label();
            $this->maxLives = max(1, min(5, (int) data_get($configuration, 'lives', 3)));
            $this->comboMilestone = max(2, min(10, (int) data_get($configuration, 'combo_milestone', 4)));
            $this->previousBestScore = app(SignalShiftGameService::class)->previousBestScore($session);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Success);
            $this->countdownMotionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Slow) * 3;

            if (data_get($session->state_snapshot, 'game') === 'signal_shift') {
                $this->hydrateSnapshot($session->state_snapshot ?? []);

                return;
            }

            $this->tutorialRequired = ! app(SignalShiftGameService::class)->hasCompletedTutorial($profile);
            $this->resetRuntimeState();
            $this->storeCheckpoint();
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function startTutorial(): void
    {
        $session = $this->session();

        if ($session === null) {
            $this->screenState = 'error';

            return;
        }

        $rule = app(SignalShiftRuleEngine::class)->tutorialRule();
        $stimuli = app(SignalShiftRuleEngine::class)->stimuliForWave(
            $rule,
            $session->level->configuration,
            ($session->getKey() * 10000) + 7,
        );

        $this->phase = 'tutorial';
        $this->gameRound = 0;
        $this->wave = 1;
        $this->waveCount = 1;
        $this->ruleText = $rule->instruction();
        $this->stimuli = $this->presentStimuli($stimuli, $rule, tutorial: true);
        $this->resolvedStimulusIds = [];
        $this->tutorialComplete = false;
        $this->tutorialFeedback = 'Find the one shape that matches both clues.';
        $this->feedbackTone = 'neutral';
        $this->storeCheckpoint();
    }

    private function prepareRound(int $roundNumber): void
    {
        $session = $this->session();

        if ($session === null) {
            $this->screenState = 'error';

            return;
        }

        $rule = app(SignalShiftRuleEngine::class)->ruleFor($session->level, $roundNumber);

        $this->phase = 'round_intro';
        $this->gameRound = $roundNumber;
        $this->wave = 0;
        $this->waveCount = $rule->waveCount;
        $this->roundSecondsRemaining = $rule->waveCount * $rule->secondsPerWave;
        $this->waveSecondsRemaining = 0;
        $this->roundCountdown = 3;
        $this->ruleText = $rule->instruction();
        $this->stimuli = [];
        $this->resolvedStimulusIds = [];
        $this->feedbackMessage = $roundNumber === 1
            ? 'Accuracy first. Speed follows.'
            : 'The rule has shifted. Reset your attention.';
        $this->feedbackTone = 'neutral';
        $this->progress = round(($roundNumber - 1) / $this->totalRounds, 3);
        $this->paused = false;
        $this->storeCheckpoint();
    }

    private function spawnWave(): void
    {
        $session = $this->session();

        if ($session === null) {
            $this->screenState = 'error';

            return;
        }

        $rule = $this->currentRule($session);
        $seed = ($session->getKey() * 10000) + ($this->gameRound * 100) + $this->wave;
        $stimuli = app(SignalShiftRuleEngine::class)->stimuliForWave(
            $rule,
            $session->level->configuration,
            $seed,
        );

        $this->stimuli = $this->presentStimuli($stimuli, $rule);
        $this->resolvedStimulusIds = [];
        $this->waveSecondsRemaining = $rule->secondsPerWave;
        $this->waveStartedAtMs = $this->currentMilliseconds();
        $this->progress = $this->gameplayProgress();
    }

    private function tickRoundCountdown(): void
    {
        if ($this->paused || $this->isSubmitting) {
            return;
        }

        if ($this->roundCountdown > 0) {
            $this->roundCountdown--;
            $this->storeCheckpoint();
            app(HapticService::class)->trigger(
                $this->roundCountdown === 0
                    ? HapticFeedback::Success
                    : HapticFeedback::Impact,
            );

            return;
        }

        $this->beginPlaying();
    }

    private function beginPlaying(): void
    {
        $this->phase = 'playing';
        $this->wave = 1;
        $this->feedbackMessage = 'Find the signal.';
        $this->feedbackTone = 'neutral';
        $this->spawnWave();
        $this->storeCheckpoint();
    }

    private function expireWave(): void
    {
        $session = $this->session();
        $target = collect($this->stimuli)->first(
            fn (array $stimulus): bool => (bool) $stimulus['is_target']
                && ! in_array($stimulus['id'], $this->resolvedStimulusIds, true),
        );

        if ($session === null || $target === null) {
            $this->advanceWaveOrFinishRound();

            return;
        }

        $this->lives = max(0, $this->lives - 1);
        $this->combo = 0;
        $this->resolvedStimulusIds[] = $target['id'];
        $this->feedbackMessage = 'Target missed. Let the next wave reset your focus.';
        $this->feedbackTone = 'danger';
        $this->floatingScore = '';
        $this->feedbackSerial++;
        $this->feedbackTicksRemaining = 1;

        try {
            app(SignalShiftGameService::class)->recordMiss(
                session: $session,
                stimulus: $target,
                gameRound: $this->gameRound,
                wave: $this->wave,
                stateSnapshot: $this->snapshot(),
            );
            $this->refreshLiveScore($session);
            app(HapticService::class)->trigger(HapticFeedback::Error);

            if ($this->lives === 0) {
                $this->failRound();

                return;
            }

            $this->advanceWaveOrFinishRound();
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function advanceWaveOrFinishRound(): void
    {
        if ($this->wave < $this->waveCount) {
            $this->wave++;
            $this->spawnWave();
            $this->storeCheckpoint();

            return;
        }

        $this->finishRound();
    }

    private function finishRound(): void
    {
        $session = $this->session();

        if ($session === null) {
            $this->screenState = 'error';

            return;
        }

        $metrics = app(SignalShiftGameService::class)->roundMetrics($session, $this->gameRound);
        $this->phase = 'round_result';
        $this->progress = round($this->gameRound / $this->totalRounds, 3);
        $this->populateRoundResult($metrics);
        $this->stimuli = [];
        $this->resolvedStimulusIds = [];
        $this->paused = false;
        $this->storeCheckpoint();
        app(HapticService::class)->trigger(HapticFeedback::Success);
    }

    private function failRound(): void
    {
        $session = $this->session();

        $this->phase = 'failed';
        $this->stimuli = [];
        $this->resolvedStimulusIds = [];
        $this->paused = false;
        $this->motivationalMessage = 'The rule won this attempt. Restart with the same difficulty and a clean slate.';

        if ($session !== null) {
            $metrics = app(SignalShiftGameService::class)->roundMetrics($session, $this->gameRound);
            $this->populateRoundResult($metrics);
            $this->storeCheckpoint();
        }

        app(HapticService::class)->trigger(HapticFeedback::Warning);
    }

    private function completeGame(): void
    {
        if ($this->isSubmitting) {
            return;
        }

        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::InProgress) {
            $this->screenState = 'error';

            return;
        }

        $this->isSubmitting = true;

        try {
            $result = app(SignalShiftGameService::class)->complete($session);

            $this->phase = 'game_result';
            $this->score = $result->score;
            $this->bestCombo = $result->bestCombo;
            $this->roundAccuracy = $this->formatAccuracy($result->accuracy);
            $this->roundReactionTime = $this->formatReactionTime($result->averageResponseMs);
            $this->roundScore = $this->formatNumber($result->score);
            $this->roundLives = $this->lives.' of '.$this->maxLives;
            $this->newPersonalBest = $this->previousBestScore === null
                || $result->score > $this->previousBestScore;
            $this->bestScoreComparison = $this->finalBestComparison($result->score);
            $this->motivationalMessage = $this->motivationFor($result->accuracy);
            $this->progress = 1.0;
            $this->isSubmitting = false;
            app(HapticService::class)->trigger(HapticFeedback::Success);
        } catch (Throwable $exception) {
            report($exception);

            $this->isSubmitting = false;
            $this->screenState = 'error';
        }
    }

    /**
     * @param  array{
     *     accuracy: float|null,
     *     average_response_ms: int|null,
     *     score: int,
     *     best_combo: int,
     *     correct_count: int,
     *     incorrect_count: int,
     *     missed_count: int
     * }  $metrics
     */
    private function populateRoundResult(array $metrics): void
    {
        $this->roundAccuracy = $this->formatAccuracy($metrics['accuracy']);
        $this->roundReactionTime = $this->formatReactionTime($metrics['average_response_ms']);
        $this->roundScore = $this->formatNumber($metrics['score']);
        $this->roundLives = $this->lives.' of '.$this->maxLives;
        $this->bestScoreComparison = $this->liveBestComparison();
        $this->motivationalMessage = $this->motivationFor($metrics['accuracy']);
    }

    private function refreshLiveScore(GameSession $session): void
    {
        $result = app(SignalShiftGameService::class)->score($session);
        $this->score = $result->score;
        $this->bestCombo = max($this->bestCombo, $result->bestCombo);
    }

    private function resetRuntimeState(): void
    {
        $this->phase = 'instructions';
        $this->tutorialComplete = false;
        $this->tutorialFeedback = 'Find the one shape that matches the rule.';
        $this->gameRound = 0;
        $this->wave = 0;
        $this->waveCount = 0;
        $this->roundSecondsRemaining = 0;
        $this->waveSecondsRemaining = 0;
        $this->roundCountdown = 3;
        $this->elapsedSeconds = 0;
        $this->lives = $this->maxLives;
        $this->combo = 0;
        $this->bestCombo = 0;
        $this->score = 0;
        $this->progress = 0.0;
        $this->ruleText = '';
        $this->stimuli = [];
        $this->resolvedStimulusIds = [];
        $this->feedbackMessage = 'Stay with the rule.';
        $this->feedbackTone = 'neutral';
        $this->floatingScore = '';
        $this->feedbackSerial = 0;
        $this->feedbackTicksRemaining = 0;
        $this->paused = false;
        $this->isSubmitting = false;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function hydrateSnapshot(array $snapshot): void
    {
        $this->phase = (string) data_get($snapshot, 'phase', 'instructions');
        $this->tutorialRequired = (bool) data_get($snapshot, 'tutorial_required', true);
        $this->tutorialComplete = (bool) data_get($snapshot, 'tutorial_complete', false);
        $this->tutorialFeedback = (string) data_get($snapshot, 'tutorial_feedback', $this->tutorialFeedback);
        $this->gameRound = (int) data_get($snapshot, 'game_round', 0);
        $this->wave = (int) data_get($snapshot, 'wave', 0);
        $this->waveCount = (int) data_get($snapshot, 'wave_count', 0);
        $this->roundSecondsRemaining = (int) data_get($snapshot, 'round_seconds_remaining', 0);
        $this->waveSecondsRemaining = (int) data_get($snapshot, 'wave_seconds_remaining', 0);
        $this->roundCountdown = (int) data_get($snapshot, 'round_countdown', 3);
        $this->elapsedSeconds = (int) data_get($snapshot, 'elapsed_seconds', 0);
        $this->lives = (int) data_get($snapshot, 'lives', $this->maxLives);
        $this->combo = (int) data_get($snapshot, 'combo', 0);
        $this->bestCombo = (int) data_get($snapshot, 'best_combo', 0);
        $this->score = (int) data_get($snapshot, 'score', 0);
        $this->progress = (float) data_get($snapshot, 'progress', 0.0);
        $this->ruleText = (string) data_get($snapshot, 'rule_text', '');
        $this->stimuli = is_array(data_get($snapshot, 'stimuli'))
            ? array_values(data_get($snapshot, 'stimuli'))
            : [];
        $this->resolvedStimulusIds = is_array(data_get($snapshot, 'resolved_stimulus_ids'))
            ? array_values(data_get($snapshot, 'resolved_stimulus_ids'))
            : [];
        $this->feedbackMessage = (string) data_get($snapshot, 'feedback_message', $this->feedbackMessage);
        $this->feedbackTone = (string) data_get($snapshot, 'feedback_tone', 'neutral');
        $this->floatingScore = (string) data_get($snapshot, 'floating_score', '');
        $this->feedbackSerial = (int) data_get($snapshot, 'feedback_serial', 0);
        $this->feedbackTicksRemaining = (int) data_get($snapshot, 'feedback_ticks_remaining', 0);
        $this->paused = (bool) data_get($snapshot, 'paused', false);
        $this->bottomSheetVisible = $this->paused;
        $this->waveStartedAtMs = $this->currentMilliseconds();

        if (in_array($this->phase, ['round_result', 'failed'], true) && $this->gameRound > 0) {
            $session = $this->session();

            if ($session !== null) {
                $this->populateRoundResult(
                    app(SignalShiftGameService::class)->roundMetrics($session, $this->gameRound),
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        return [
            'game' => 'signal_shift',
            'prepared' => true,
            'phase' => $this->phase,
            'tutorial_required' => $this->tutorialRequired,
            'tutorial_complete' => $this->tutorialComplete,
            'tutorial_feedback' => $this->tutorialFeedback,
            'game_round' => $this->gameRound,
            'wave' => $this->wave,
            'wave_count' => $this->waveCount,
            'round_seconds_remaining' => $this->roundSecondsRemaining,
            'wave_seconds_remaining' => $this->waveSecondsRemaining,
            'round_countdown' => $this->roundCountdown,
            'elapsed_seconds' => $this->elapsedSeconds,
            'lives' => $this->lives,
            'combo' => $this->combo,
            'best_combo' => $this->bestCombo,
            'score' => $this->score,
            'progress' => $this->progress,
            'rule_text' => $this->ruleText,
            'stimuli' => $this->stimuli,
            'resolved_stimulus_ids' => $this->resolvedStimulusIds,
            'feedback_message' => $this->feedbackMessage,
            'feedback_tone' => $this->feedbackTone,
            'floating_score' => $this->floatingScore,
            'feedback_serial' => $this->feedbackSerial,
            'feedback_ticks_remaining' => $this->feedbackTicksRemaining,
            'paused' => $this->paused,
        ];
    }

    private function storeCheckpoint(): void
    {
        $session = $this->session();

        if ($session === null || $session->status !== SessionStatus::InProgress) {
            return;
        }

        app(GameSessionService::class)->checkpoint($session, $this->snapshot());
    }

    private function session(): ?GameSession
    {
        $profile = app(ProfileService::class)->current();

        if ($profile === null || $this->sessionId === null) {
            return null;
        }

        return GameSession::query()
            ->whereKey($this->sessionId)
            ->where('profile_id', $profile->getKey())
            ->with([
                'profile',
                'game',
                'level',
                'rounds',
                'workoutItem.workout.items.game',
                'workoutItem.workout.items.level',
                'workoutItem.workout.items.sessions',
            ])
            ->first();
    }

    private function currentRule(GameSession $session): SignalShiftRule
    {
        return app(SignalShiftRuleEngine::class)->ruleFor($session->level, $this->gameRound);
    }

    /**
     * @param  list<array<string, mixed>>  $stimuli
     * @return list<array<string, mixed>>
     */
    private function presentStimuli(
        array $stimuli,
        SignalShiftRule $rule,
        bool $tutorial = false,
    ): array {
        return array_map(function (array $stimulus) use ($rule, $tutorial): array {
            $direction = (string) $stimulus['direction'];
            $movement = $this->reducedMotion || ! $stimulus['moving'] || $tutorial
                ? 0
                : match ($direction) {
                    'left', 'up' => -6,
                    default => 6,
                };
            $shape = (string) $stimulus['shape'];
            $baseRotation = $shape === 'diamond' ? 45 : 0;

            return [
                ...$stimulus,
                'color_class' => match ($stimulus['color']) {
                    'teal' => 'bg-theme-accent',
                    'gold' => 'bg-theme-warning',
                    'coral' => 'bg-theme-danger',
                    default => 'bg-theme-primary-text',
                },
                'dimension' => $stimulus['size'] === 'large' ? 58 : 42,
                'rotation' => $baseRotation + ((bool) $stimulus['rotated'] ? 18 : 0),
                'translate_x' => in_array($direction, ['left', 'right'], true) ? $movement : 0,
                'translate_y' => in_array($direction, ['up', 'down'], true) ? $movement : 0,
                'motion_duration' => $this->reducedMotion
                    ? 0
                    : (int) round($this->motionDuration / $rule->speedModifier),
                'press_method' => $tutorial
                    ? "tapTutorialStimulus('{$stimulus['id']}')"
                    : "tapStimulus('{$stimulus['id']}')",
            ];
        }, $stimuli);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findStimulus(string $stimulusId): ?array
    {
        return collect($this->stimuli)->firstWhere('id', $stimulusId);
    }

    private function gameplayProgress(): float
    {
        $completedRounds = max(0, $this->gameRound - 1);
        $roundFraction = $this->waveCount === 0
            ? 0
            : max(0, $this->wave - 1) / $this->waveCount;

        return round(($completedRounds + $roundFraction) / $this->totalRounds, 3);
    }

    private function liveBestComparison(): string
    {
        if ($this->previousBestScore === null) {
            return 'This is your first Signal Shift benchmark.';
        }

        if ($this->score > $this->previousBestScore) {
            return 'You are '.$this->formatNumber($this->score - $this->previousBestScore).' points ahead of your best.';
        }

        return $this->formatNumber($this->previousBestScore - $this->score).' points to your best score.';
    }

    private function finalBestComparison(int $score): string
    {
        if ($this->previousBestScore === null) {
            return 'First benchmark set at '.$this->formatNumber($score).' points.';
        }

        if ($score > $this->previousBestScore) {
            return 'New best by '.$this->formatNumber($score - $this->previousBestScore).' points.';
        }

        if ($score === $this->previousBestScore) {
            return 'You matched your personal best.';
        }

        return $this->formatNumber($this->previousBestScore - $score).' points below your best.';
    }

    private function motivationFor(?float $accuracy): string
    {
        return match (true) {
            $accuracy === null => 'Every attempt creates a clearer next step.',
            $accuracy >= 90 => 'Exceptional control. You adapted without losing precision.',
            $accuracy >= 75 => 'Strong focus. The rule changes are becoming familiar.',
            $accuracy >= 55 => 'Good recovery. Slow the first glance, then respond.',
            default => 'Reset, read the full rule, and let accuracy lead speed.',
        };
    }

    private function formatAccuracy(?float $accuracy): string
    {
        return $accuracy === null
            ? 'Not recorded'
            : number_format($accuracy, 1, '.', ',').'%';
    }

    private function formatReactionTime(?int $responseMs): string
    {
        return $responseMs === null
            ? 'Not recorded'
            : $this->formatNumber($responseMs).' ms';
    }

    private function formatNumber(int $value): string
    {
        return number_format($value, 0, '.', ',');
    }

    private function currentMilliseconds(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    private function screenTransition(): Transition
    {
        return $this->reducedMotion ? Transition::None : Transition::Fade;
    }
}
