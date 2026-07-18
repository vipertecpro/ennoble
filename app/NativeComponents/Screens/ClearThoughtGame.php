<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\ClearThought\ClearThoughtGameService;
use App\Domain\Games\GameSessionService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\ClearThoughtMode;
use App\Enums\GameType;
use App\Enums\SessionStatus;
use App\Models\Challenge;
use App\Models\GameSession;
use App\NativeUI\Dialogs\InteractsWithDialogs;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

final class ClearThoughtGame extends NativeComponent
{
    use InteractsWithDialogs;

    public string $screenState = 'content';

    public string $errorMessage = 'Clear Thought could not restore this checkpoint. Your completed evidence remains safe.';

    public ?int $sessionId = null;

    public string $phase = 'instructions';

    public string $difficulty = '';

    public int $roundNumber = 0;

    public int $totalRounds = 0;

    public int $maxAttempts = 2;

    public int $attemptsUsed = 0;

    public bool $hintUsed = false;

    public bool $hintVisible = false;

    public string $hintText = '';

    public string $mode = '';

    public string $modeLabel = '';

    public string $modeGuidance = '';

    public string $prompt = '';

    /**
     * @var list<array{id: string, text: string, state: string}>
     */
    public array $options = [];

    /**
     * @var list<array{id: string, text: string, selected: bool}>
     */
    public array $words = [];

    /**
     * @var list<array{id: string, text: string, used: bool}>
     */
    public array $segments = [];

    /**
     * @var list<array{id: string, text: string}>
     */
    public array $arranged = [];

    /**
     * @var list<string>
     */
    public array $roundOutcomes = [];

    /**
     * @var list<int>
     */
    public array $challengeIds = [];

    public int $challengeStartedAtMs = 0;

    public string $feedbackTone = 'neutral';

    public string $feedbackMessage = '';

    public string $reflectionEyebrow = '';

    public string $reflectionTitle = '';

    public string $reflectionAnswer = '';

    public string $explanation = '';

    public string $resultScore = '0';

    public string $resultAccuracy = 'Not recorded';

    public string $resultResponse = 'Not recorded';

    public string $resultClarity = '';

    public string $resultHints = '';

    public string $bestScoreComparison = 'Complete the session to set your pace.';

    public string $motivationalMessage = 'Read once for meaning. Then choose with confidence.';

    public ?int $previousBestScore = null;

    public bool $newPersonalBest = false;

    public bool $isSubmitting = false;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public int $feedbackMotionDuration = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->sessionId = (int) $this->param('session');
        $this->loadSession();
    }

    public function render(): Element
    {
        return $this->view('screens.clear-thought-game');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->hidden();
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    /**
     * Leave the instructions and present the first unanswered challenge.
     */
    public function beginClearThought(): void
    {
        if ($this->phase !== 'instructions') {
            return;
        }

        app(HapticService::class)->trigger(HapticFeedback::Impact);
        $this->presentChallenge($this->roundNumber === 0 ? 1 : $this->roundNumber);
    }

    /**
     * Submit a clearest-sentence choice as one authoritative attempt.
     */
    public function chooseOption(string $optionId): void
    {
        if ($this->phase !== 'challenge' || $this->mode !== ClearThoughtMode::ChooseClearestSentence->value) {
            return;
        }

        $option = collect($this->options)->firstWhere('id', $optionId);

        if ($option === null || $option['state'] === 'wrong') {
            return;
        }

        $this->submitResponse(['option' => $optionId], function () use ($optionId): void {
            $this->options = collect($this->options)
                ->map(fn (array $option): array => $option['id'] === $optionId
                    ? [...$option, 'state' => 'wrong']
                    : $option)
                ->values()
                ->all();
        });
    }

    /**
     * Toggle one word in the remove-the-noise selection.
     */
    public function toggleWord(string $wordId): void
    {
        if ($this->phase !== 'challenge' || $this->mode !== ClearThoughtMode::RemoveUnnecessaryWords->value) {
            return;
        }

        $this->words = collect($this->words)
            ->map(fn (array $word): array => $word['id'] === $wordId
                ? [...$word, 'selected' => ! $word['selected']]
                : $word)
            ->values()
            ->all();
        $this->feedbackTone = 'neutral';
        $this->feedbackMessage = '';
        app(HapticService::class)->trigger(HapticFeedback::Selection);
        $this->storeCheckpoint();
    }

    /**
     * Submit the selected unnecessary words as one authoritative attempt.
     */
    public function submitWords(): void
    {
        if ($this->phase !== 'challenge' || $this->mode !== ClearThoughtMode::RemoveUnnecessaryWords->value) {
            return;
        }

        $selected = collect($this->words)->where('selected', true)->pluck('id')->values()->all();

        if ($selected === []) {
            $this->feedbackTone = 'danger';
            $this->feedbackMessage = 'Select at least one word that the sentence does not need.';

            return;
        }

        $this->submitResponse(['selected' => $selected]);
    }

    /**
     * Place the next segment while rebuilding the sentence order.
     */
    public function tapSegment(string $segmentId): void
    {
        if ($this->phase !== 'challenge' || $this->mode !== ClearThoughtMode::ReorderSentence->value) {
            return;
        }

        $segment = collect($this->segments)->firstWhere('id', $segmentId);

        if ($segment === null || $segment['used']) {
            return;
        }

        $this->segments = collect($this->segments)
            ->map(fn (array $item): array => $item['id'] === $segmentId
                ? [...$item, 'used' => true]
                : $item)
            ->values()
            ->all();
        $this->arranged = [...$this->arranged, ['id' => $segment['id'], 'text' => $segment['text']]];
        $this->feedbackTone = 'neutral';
        $this->feedbackMessage = '';
        app(HapticService::class)->trigger(HapticFeedback::Selection);
        $this->storeCheckpoint();
    }

    /**
     * Return an arranged segment to the pool.
     */
    public function removeArranged(string $segmentId): void
    {
        if ($this->phase !== 'challenge' || $this->mode !== ClearThoughtMode::ReorderSentence->value) {
            return;
        }

        $this->arranged = collect($this->arranged)
            ->reject(fn (array $segment): bool => $segment['id'] === $segmentId)
            ->values()
            ->all();
        $this->segments = collect($this->segments)
            ->map(fn (array $segment): array => $segment['id'] === $segmentId
                ? [...$segment, 'used' => false]
                : $segment)
            ->values()
            ->all();
        app(HapticService::class)->trigger(HapticFeedback::Selection);
        $this->storeCheckpoint();
    }

    /**
     * Submit the rebuilt sentence order as one authoritative attempt.
     */
    public function submitOrder(): void
    {
        if ($this->phase !== 'challenge' || $this->mode !== ClearThoughtMode::ReorderSentence->value) {
            return;
        }

        if (count($this->arranged) < count($this->segments)) {
            $this->feedbackTone = 'danger';
            $this->feedbackMessage = 'Place every segment before checking the sentence.';

            return;
        }

        $this->submitResponse([
            'segments' => collect($this->arranged)->pluck('id')->values()->all(),
        ], function (): void {
            $this->arranged = [];
            $this->segments = collect($this->segments)
                ->map(fn (array $segment): array => [...$segment, 'used' => false])
                ->values()
                ->all();
        });
    }

    /**
     * Reveal the bundled hint at a fixed score cost.
     */
    public function revealHint(): void
    {
        if ($this->phase !== 'challenge' || $this->hintText === '' || $this->hintVisible) {
            return;
        }

        $this->hintUsed = true;
        $this->hintVisible = true;
        app(HapticService::class)->trigger(HapticFeedback::Selection);
        $this->storeCheckpoint();
    }

    /**
     * Advance from a round reflection to the next challenge or the final result.
     */
    public function continueAfterReflection(): void
    {
        if ($this->phase !== 'reflection') {
            return;
        }

        if ($this->roundNumber >= $this->totalRounds) {
            $this->completeGame();

            return;
        }

        $this->presentChallenge($this->roundNumber + 1);
    }

    public function requestExit(): void
    {
        $this->dialogVisible = true;
        $this->storeCheckpoint();
        app(HapticService::class)->trigger(HapticFeedback::Warning);
    }

    public function cancelExit(): void
    {
        $this->dialogVisible = false;
        $this->challengeStartedAtMs = $this->currentMilliseconds();
        $this->storeCheckpoint();
    }

    public function confirmExit(): void
    {
        $this->dialogVisible = false;
        $this->storeCheckpoint();
        $this->replace('/')->transition($this->screenTransition());
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
                || $session->game->type !== GameType::ClearThought
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
            $service = app(ClearThoughtGameService::class);
            $challenges = $service->challengesFor($session);

            $this->difficulty = $session->level->difficulty->label();
            $this->maxAttempts = max(1, (int) data_get($session->level->configuration, 'max_attempts', 1));
            $this->previousBestScore = $service->previousBestScore($session);
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);
            $this->feedbackMotionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Success);

            if (data_get($session->state_snapshot, 'game') === 'clear_thought') {
                $this->hydrateSnapshot($session->state_snapshot ?? []);

                return;
            }

            $this->challengeIds = $challenges->pluck('id')->all();
            $this->totalRounds = count($this->challengeIds);
            $this->roundNumber = 0;
            $this->roundOutcomes = [];
            $this->phase = 'instructions';
            $this->storeCheckpoint();
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    /**
     * Validate one attempt, persist final outcomes, and stage the reflection.
     *
     * @param  array<string, mixed>  $response
     */
    private function submitResponse(array $response, ?callable $onRetryableMiss = null): void
    {
        $session = $this->session();
        $challenge = $this->currentChallenge();

        if ($session === null || $challenge === null || $session->status !== SessionStatus::InProgress) {
            $this->screenState = 'error';

            return;
        }

        try {
            $service = app(ClearThoughtGameService::class);
            $this->attemptsUsed++;
            $correct = $service->isCorrect($challenge, $response);

            if (! $correct && $this->attemptsUsed < $this->maxAttempts) {
                $this->feedbackTone = 'danger';
                $this->feedbackMessage = 'Not quite. Read it once more and adjust.';

                if ($onRetryableMiss !== null) {
                    $onRetryableMiss();
                }

                app(HapticService::class)->trigger(HapticFeedback::Error);
                $this->storeCheckpoint();

                return;
            }

            $responseMs = max(1, $this->currentMilliseconds() - $this->challengeStartedAtMs);

            $this->roundOutcomes = [...$this->roundOutcomes, $correct ? 'correct' : 'incorrect'];
            $this->stageReflection($challenge, $correct);
            $service->recordAnswer(
                session: $session,
                challenge: $challenge,
                correct: $correct,
                responseMs: $responseMs,
                attempts: $this->attemptsUsed,
                hintUsed: $this->hintUsed,
                response: $response,
                stateSnapshot: $this->snapshot(),
            );
            app(HapticService::class)->trigger(
                $correct ? HapticFeedback::Success : HapticFeedback::Error,
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function stageReflection(Challenge $challenge, bool $correct): void
    {
        $this->phase = 'reflection';
        $this->feedbackTone = $correct ? 'success' : 'danger';
        $this->feedbackMessage = '';
        $this->reflectionEyebrow = $correct
            ? 'CLEAR · ROUND '.$this->roundNumber.' OF '.$this->totalRounds
            : 'A CLEARER VERSION · ROUND '.$this->roundNumber.' OF '.$this->totalRounds;
        $this->reflectionTitle = $correct
            ? 'Precisely put.'
            : 'Here is the clear form.';
        $this->reflectionAnswer = (string) data_get($challenge->payload, 'answer_text', '');
        $this->explanation = $challenge->explanation;
    }

    private function presentChallenge(int $roundNumber): void
    {
        $challenge = $this->challengeAt($roundNumber);

        if ($challenge === null) {
            $this->screenState = 'error';

            return;
        }

        $this->roundNumber = $roundNumber;
        $this->phase = 'challenge';
        $this->attemptsUsed = 0;
        $this->hintUsed = false;
        $this->hintVisible = false;
        $this->hintText = (string) ($challenge->hint ?? '');
        $this->mode = $challenge->mode->value;
        $this->modeLabel = $this->modeLabelFor($challenge->mode);
        $this->modeGuidance = $this->modeGuidanceFor($challenge->mode);
        $this->prompt = $challenge->prompt;
        $this->feedbackTone = 'neutral';
        $this->feedbackMessage = '';
        $this->reflectionAnswer = '';
        $this->explanation = '';
        $this->options = collect(data_get($challenge->payload, 'options', []))
            ->map(fn (array $option): array => [
                'id' => (string) $option['id'],
                'text' => (string) $option['text'],
                'state' => 'idle',
            ])
            ->values()
            ->all();
        $this->words = collect(data_get($challenge->payload, 'words', []))
            ->map(fn (array $word): array => [
                'id' => (string) $word['id'],
                'text' => (string) $word['text'],
                'selected' => false,
            ])
            ->values()
            ->all();
        $this->segments = collect(data_get($challenge->payload, 'segments', []))
            ->map(fn (array $segment): array => [
                'id' => (string) $segment['id'],
                'text' => (string) $segment['text'],
                'used' => false,
            ])
            ->values()
            ->all();
        $this->arranged = [];
        $this->challengeStartedAtMs = $this->currentMilliseconds();
        $this->storeCheckpoint();
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
            $result = app(ClearThoughtGameService::class)->complete($session);
            $correctCount = $result->correctCount;

            $this->phase = 'game_result';
            $this->resultScore = number_format($result->score);
            $this->resultAccuracy = $result->accuracy === null
                ? 'Not recorded'
                : rtrim(rtrim(number_format($result->accuracy, 1), '0'), '.').'%';
            $this->resultResponse = $this->formatResponseTime($result->averageResponseMs);
            $this->resultClarity = $correctCount.' of '.$this->totalRounds.' clear';
            $this->resultHints = $result->hintCount === 0
                ? 'No hints needed'
                : ($result->hintCount === 1 ? '1 hint used' : $result->hintCount.' hints used');
            $this->newPersonalBest = $this->previousBestScore === null
                || $result->score > $this->previousBestScore;
            $this->bestScoreComparison = $this->finalBestComparison($result->score);
            $this->motivationalMessage = $this->motivationFor($result->accuracy);
            $this->isSubmitting = false;
            app(HapticService::class)->trigger(HapticFeedback::Success);
        } catch (Throwable $exception) {
            report($exception);

            $this->isSubmitting = false;
            $this->screenState = 'error';
        }
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function hydrateSnapshot(array $snapshot): void
    {
        $this->phase = (string) data_get($snapshot, 'phase', 'instructions');
        $this->challengeIds = collect(data_get($snapshot, 'challenge_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
        $this->totalRounds = count($this->challengeIds);
        $this->roundNumber = (int) data_get($snapshot, 'round_number', 0);
        $this->attemptsUsed = (int) data_get($snapshot, 'attempts_used', 0);
        $this->hintUsed = (bool) data_get($snapshot, 'hint_used', false);
        $this->hintVisible = (bool) data_get($snapshot, 'hint_visible', false);
        $this->roundOutcomes = collect(data_get($snapshot, 'round_outcomes', []))
            ->map(fn (mixed $outcome): string => (string) $outcome)
            ->values()
            ->all();

        if ($this->phase === 'challenge' && $this->roundNumber > 0) {
            $selectedWordIds = collect(data_get($snapshot, 'selected_word_ids', []))
                ->map(fn (mixed $id): string => (string) $id)
                ->all();
            $arrangedIds = collect(data_get($snapshot, 'arranged_ids', []))
                ->map(fn (mixed $id): string => (string) $id)
                ->all();
            $wrongOptionIds = collect(data_get($snapshot, 'wrong_option_ids', []))
                ->map(fn (mixed $id): string => (string) $id)
                ->all();
            $attempts = $this->attemptsUsed;
            $hintUsed = $this->hintUsed;
            $hintVisible = $this->hintVisible;

            $this->presentChallenge($this->roundNumber);

            $this->attemptsUsed = $attempts;
            $this->hintUsed = $hintUsed;
            $this->hintVisible = $hintVisible;
            $this->options = collect($this->options)
                ->map(fn (array $option): array => in_array($option['id'], $wrongOptionIds, true)
                    ? [...$option, 'state' => 'wrong']
                    : $option)
                ->values()
                ->all();
            $this->words = collect($this->words)
                ->map(fn (array $word): array => [...$word, 'selected' => in_array($word['id'], $selectedWordIds, true)])
                ->values()
                ->all();

            foreach ($arrangedIds as $segmentId) {
                $segment = collect($this->segments)->firstWhere('id', $segmentId);

                if ($segment !== null && ! $segment['used']) {
                    $this->segments = collect($this->segments)
                        ->map(fn (array $item): array => $item['id'] === $segmentId
                            ? [...$item, 'used' => true]
                            : $item)
                        ->values()
                        ->all();
                    $this->arranged = [...$this->arranged, ['id' => $segment['id'], 'text' => $segment['text']]];
                }
            }

            return;
        }

        if ($this->phase === 'reflection' && $this->roundNumber > 0) {
            $challenge = $this->challengeAt($this->roundNumber);

            if ($challenge !== null) {
                $lastOutcome = $this->roundOutcomes[$this->roundNumber - 1] ?? 'incorrect';

                $this->stageReflection($challenge, $lastOutcome === 'correct');
            }

            return;
        }

        if ($this->phase === 'game_result') {
            $this->phase = 'reflection';
            $this->continueAfterReflection();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        return [
            'game' => 'clear_thought',
            'prepared' => true,
            'phase' => $this->phase,
            'challenge_ids' => $this->challengeIds,
            'round_number' => $this->roundNumber,
            'attempts_used' => $this->attemptsUsed,
            'hint_used' => $this->hintUsed,
            'hint_visible' => $this->hintVisible,
            'round_outcomes' => $this->roundOutcomes,
            'selected_word_ids' => collect($this->words)->where('selected', true)->pluck('id')->values()->all(),
            'arranged_ids' => collect($this->arranged)->pluck('id')->values()->all(),
            'wrong_option_ids' => collect($this->options)->where('state', 'wrong')->pluck('id')->values()->all(),
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

        if ($profile === null || $this->sessionId === null || $this->sessionId < 1) {
            return null;
        }

        return GameSession::query()
            ->whereKey($this->sessionId)
            ->where('profile_id', $profile->getKey())
            ->with(['game', 'level', 'profile', 'workoutItem.workout.items.game'])
            ->first();
    }

    private function currentChallenge(): ?Challenge
    {
        return $this->challengeAt($this->roundNumber);
    }

    private function challengeAt(int $roundNumber): ?Challenge
    {
        $challengeId = $this->challengeIds[$roundNumber - 1] ?? null;

        if ($challengeId === null) {
            return null;
        }

        return Challenge::query()->active()->find($challengeId);
    }

    private function modeLabelFor(ClearThoughtMode $mode): string
    {
        return match ($mode) {
            ClearThoughtMode::RemoveUnnecessaryWords => 'Remove the noise',
            ClearThoughtMode::ReorderSentence => 'Rebuild the order',
            ClearThoughtMode::ChooseClearestSentence => 'Choose the clearest',
        };
    }

    private function modeGuidanceFor(ClearThoughtMode $mode): string
    {
        return match ($mode) {
            ClearThoughtMode::RemoveUnnecessaryWords => 'Tap every word the sentence does not need, then check it.',
            ClearThoughtMode::ReorderSentence => 'Tap the segments in the order that reads most naturally.',
            ClearThoughtMode::ChooseClearestSentence => 'Tap the version that says it best.',
        };
    }

    private function screenTransition(): Transition
    {
        return $this->reducedMotion ? Transition::None : Transition::FadeFromBottom;
    }

    private function finalBestComparison(int $score): string
    {
        if ($this->previousBestScore === null) {
            return 'First benchmark set at '.number_format($score).' points.';
        }

        if ($score > $this->previousBestScore) {
            return 'New personal best, up from '.number_format($this->previousBestScore).'.';
        }

        return 'Personal best remains '.number_format($this->previousBestScore).'.';
    }

    private function motivationFor(?float $accuracy): string
    {
        return match (true) {
            $accuracy === null => 'Every sentence you shaped is saved privately on this device.',
            $accuracy >= 90 => 'Your editing eye is precise. Keep it.',
            $accuracy >= 70 => 'Strong clarity instincts. The exact form is close.',
            $accuracy >= 50 => 'You found the meaning. Precision follows practice.',
            default => 'Each clearer sentence trains the next one.',
        };
    }

    private function formatResponseTime(?int $responseMs): string
    {
        if ($responseMs === null) {
            return 'Not recorded';
        }

        if ($responseMs < 1000) {
            return $responseMs.' ms';
        }

        return number_format($responseMs / 1000, 1).' s';
    }

    private function currentMilliseconds(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
