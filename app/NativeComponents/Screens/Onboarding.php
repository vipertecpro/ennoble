<?php

namespace App\NativeComponents\Screens;

use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Enums\Difficulty;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Illuminate\Support\Str;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

final class Onboarding extends NativeComponent
{
    public const TOTAL_STEPS = 6;

    public int $currentStep = 1;

    public string $trainingGoal = '';

    public string $difficulty = '';

    public string $displayName = '';

    public string $themePreference = ThemePreference::System->value;

    public bool $soundEnabled = true;

    public bool $hapticsEnabled = true;

    public bool $reducedMotion = false;

    public bool $isSaving = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $theme = app(ThemeManager::class);
        $theme->applyCurrent();

        if (app(OnboardingService::class)->isComplete()) {
            $transition = $theme->prefersReducedMotion()
                ? Transition::None
                : Transition::Fade;

            $this->replace('/')->transition($transition);
        }
    }

    public function render(): Element
    {
        return $this->view('screens.onboarding');
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
     * Advance when the current onboarding input is valid.
     */
    public function nextStep(): void
    {
        if (! $this->canContinue() || $this->currentStep >= self::TOTAL_STEPS) {
            return;
        }

        $this->errorMessage = null;
        $this->currentStep++;
    }

    /**
     * Move back one onboarding step without leaving the flow.
     */
    public function previousStep(): void
    {
        if ($this->currentStep <= 1) {
            return;
        }

        $this->errorMessage = null;
        $this->currentStep--;
    }

    /**
     * Keep the Android back button inside the onboarding journey.
     */
    public function onBackPressed(): void
    {
        if ($this->currentStep > 1) {
            $this->previousStep();

            return;
        }

        parent::onBackPressed();
    }

    /**
     * Persist the complete onboarding journey and enter the existing shell.
     */
    public function completeOnboarding(): void
    {
        if ($this->currentStep !== self::TOTAL_STEPS || ! $this->canContinue()) {
            return;
        }

        $this->isSaving = true;
        $this->errorMessage = null;

        try {
            app(OnboardingService::class)->complete(
                displayName: $this->displayName,
                trainingGoal: TrainingGoal::from($this->trainingGoal),
                difficulty: Difficulty::from($this->difficulty),
                themePreference: ThemePreference::from($this->themePreference),
                soundEnabled: $this->soundEnabled,
                hapticsEnabled: $this->hapticsEnabled,
                reducedMotion: $this->reducedMotion,
            );

            app(ThemeManager::class)->apply(ThemePreference::from($this->themePreference));
            app(HapticService::class)->trigger(HapticFeedback::Success);

            $transition = $this->reducedMotion
                ? Transition::None
                : Transition::Fade;

            $this->replace('/')->transition($transition);
        } catch (Throwable $exception) {
            report($exception);

            $this->errorMessage = 'Your choices could not be saved. Please try again.';
            $this->isSaving = false;
        }
    }

    /**
     * Validate the appearance choice before it is applied on the next screen.
     */
    public function updatedThemePreference(string $value): void
    {
        $preference = ThemePreference::tryFrom($value);

        if ($preference === null) {
            $this->themePreference = ThemePreference::System->value;

            return;
        }
    }

    /**
     * Reject forged goal values and confirm valid native selections.
     */
    public function updatedTrainingGoal(string $value): void
    {
        if (TrainingGoal::tryFrom($value) === null) {
            $this->trainingGoal = '';

            return;
        }

        app(HapticService::class)->trigger(HapticFeedback::Selection);
    }

    /**
     * Reject forged difficulty values and confirm valid native selections.
     */
    public function updatedDifficulty(string $value): void
    {
        if (Difficulty::tryFrom($value) === null) {
            $this->difficulty = '';

            return;
        }

        app(HapticService::class)->trigger(HapticFeedback::Selection);
    }

    /**
     * Determine whether the current step can advance.
     */
    public function canContinue(): bool
    {
        return match ($this->currentStep) {
            2 => TrainingGoal::tryFrom($this->trainingGoal) !== null,
            3 => Difficulty::tryFrom($this->difficulty) !== null,
            4 => $this->isDisplayNameValid(),
            6 => TrainingGoal::tryFrom($this->trainingGoal) !== null
                && Difficulty::tryFrom($this->difficulty) !== null
                && ThemePreference::tryFrom($this->themePreference) !== null
                && $this->isDisplayNameValid(),
            default => true,
        };
    }

    /**
     * Return the reduced-motion-aware duration for onboarding elements.
     */
    public function motionDuration(MotionToken $token = MotionToken::Normal): int
    {
        if ($this->reducedMotion) {
            return 0;
        }

        return DesignTokens::motionDuration($token);
    }

    /**
     * Return the selected goal's display label.
     */
    public function trainingGoalLabel(): string
    {
        return TrainingGoal::tryFrom($this->trainingGoal)?->label() ?? 'Not selected';
    }

    /**
     * Return the selected difficulty's display label.
     */
    public function difficultyLabel(): string
    {
        return Difficulty::tryFrom($this->difficulty)?->label() ?? 'Not selected';
    }

    /**
     * Return the selected theme's display label.
     */
    public function themeLabel(): string
    {
        return ThemePreference::tryFrom($this->themePreference)?->label() ?? 'Use Device Setting';
    }

    /**
     * Return an intentional summary when the optional display name is empty.
     */
    public function displayNameSummary(): string
    {
        $normalizedDisplayName = Str::squish($this->displayName);

        return $normalizedDisplayName === '' ? 'Not set' : $normalizedDisplayName;
    }

    /**
     * Validate the optional local display name at the domain's shared limit.
     */
    public function isDisplayNameValid(): bool
    {
        return Str::length(Str::squish($this->displayName))
            <= ProfileService::DISPLAY_NAME_MAX_LENGTH;
    }
}
