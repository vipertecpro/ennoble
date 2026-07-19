<?php

namespace App\NativeComponents\Screens;

use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\Difficulty;
use App\Enums\TrainingGoal;
use App\Models\Profile as LocalProfile;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Feedback\ToastService;
use App\NativeUI\Feedback\ToastType;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Illuminate\Support\Str;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Throwable;

/**
 * The editable local identity — display name, training focus and pace.
 * Reached from Profile; changes stay on this device.
 */
final class MyDetails extends NativeComponent
{
    public string $screenState = 'content';

    public string $screenError = 'Your details could not be loaded. Please try again.';

    public string $displayName = '';

    public string $trainingGoal = TrainingGoal::Balanced->value;

    public string $difficulty = Difficulty::Intermediate->value;

    public string $savedDisplayName = '';

    public string $savedTrainingGoal = TrainingGoal::Balanced->value;

    public string $savedDifficulty = Difficulty::Intermediate->value;

    public bool $isSaving = false;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $this->replace('/onboarding');

            return;
        }

        $this->loadProfile();
    }

    public function render(): Element
    {
        return $this->view('screens.my-details');
    }

    public function onResume(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->loadProfile();
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('My Details')
            ->subtitle('Kept private on this device')
            ->back(true);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    public function goBack(): void
    {
        $this->back();
    }

    /**
     * Reject forged goal values and confirm valid native selections.
     */
    public function updatedTrainingGoal(string $value): void
    {
        if (TrainingGoal::tryFrom($value) === null) {
            $this->trainingGoal = $this->savedTrainingGoal;

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
            $this->difficulty = $this->savedDifficulty;

            return;
        }

        app(HapticService::class)->trigger(HapticFeedback::Selection);
    }

    /**
     * Persist edited local details through the existing profile service.
     */
    public function saveDetails(): void
    {
        if (! $this->hasUnsavedChanges() || ! $this->isDisplayNameValid()) {
            return;
        }

        $this->isSaving = true;

        try {
            app(ProfileService::class)->createOrUpdate(
                displayName: $this->displayName,
                trainingGoal: TrainingGoal::from($this->trainingGoal),
                difficulty: Difficulty::from($this->difficulty),
            );

            app(HapticService::class)->trigger(HapticFeedback::Success);
            app(ToastService::class)->show('Details saved on this device.', ToastType::Success);

            $this->loadProfile();
        } catch (Throwable $exception) {
            report($exception);

            app(ToastService::class)->show(
                'Your details could not be saved. Please try again.',
                ToastType::Error,
            );
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Determine whether edits differ from the persisted local profile.
     */
    public function hasUnsavedChanges(): bool
    {
        return Str::squish($this->displayName) !== $this->savedDisplayName
            || $this->trainingGoal !== $this->savedTrainingGoal
            || $this->difficulty !== $this->savedDifficulty;
    }

    /**
     * Validate the optional local display name at the domain's shared limit.
     */
    public function isDisplayNameValid(): bool
    {
        return Str::length(Str::squish($this->displayName))
            <= ProfileService::DISPLAY_NAME_MAX_LENGTH;
    }

    /**
     * Retry the complete screen after a recoverable local failure.
     */
    public function retryMyDetails(): void
    {
        $this->loadProfile();
    }

    private function loadProfile(): void
    {
        $this->screenState = 'content';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null || $profile->onboarding_completed_at === null) {
                $this->replace('/onboarding');

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);

            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);

            $this->mapIdentity($profile);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function mapIdentity(LocalProfile $profile): void
    {
        $normalizedName = Str::squish($profile->display_name ?? '');

        $this->displayName = $normalizedName;
        $this->savedDisplayName = $normalizedName;
        $this->trainingGoal = $profile->training_goal->value;
        $this->savedTrainingGoal = $profile->training_goal->value;
        $this->difficulty = $profile->difficulty_preference->value;
        $this->savedDifficulty = $profile->difficulty_preference->value;
    }
}
