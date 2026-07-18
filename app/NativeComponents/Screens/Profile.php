<?php

namespace App\NativeComponents\Screens;

use App\Domain\Achievements\AchievementService;
use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\Difficulty;
use App\Enums\TrainingGoal;
use App\Models\Achievement;
use App\Models\Profile as LocalProfile;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Feedback\ToastService;
use App\NativeUI\Feedback\ToastType;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Illuminate\Support\Str;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

#[Lazy]
final class Profile extends NativeComponent
{
    public string $screenState = 'content';

    public string $screenError = 'Your profile could not be loaded. Please try again.';

    public string $displayName = '';

    public string $trainingGoal = TrainingGoal::Balanced->value;

    public string $difficulty = Difficulty::Intermediate->value;

    public string $savedDisplayName = '';

    public string $savedTrainingGoal = TrainingGoal::Balanced->value;

    public string $savedDifficulty = Difficulty::Intermediate->value;

    public bool $isSaving = false;

    public string $monogram = '';

    public string $identityName = 'Friend';

    public string $memberSince = '';

    public string $goalLabel = '';

    public string $paceLabel = '';

    public string $workoutsLabel = '0';

    public string $streakLabel = '0';

    public string $achievementsLabel = '0';

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    /**
     * Apply the saved theme, enforce onboarding, and assemble the local identity.
     */
    public function mount(): void
    {
        $theme = app(ThemeManager::class);
        $theme->applyCurrent();

        if (! app(OnboardingService::class)->isComplete()) {
            $transition = $theme->prefersReducedMotion()
                ? Transition::None
                : Transition::Fade;

            $this->replace('/onboarding')->transition($transition);

            return;
        }

        $this->loadProfile();
    }

    public function render(): Element
    {
        return $this->view('screens.profile');
    }

    /**
     * Refresh identity and evidence after returning from another native screen.
     */
    public function onResume(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->loadProfile();
    }

    /**
     * Supply the Profile title and purpose to native chrome.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Profile')
            ->subtitle('Your private local identity')
            ->back(false);
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
            app(ToastService::class)->show('Profile saved on this device.', ToastType::Success);

            $this->loadProfile();
        } catch (Throwable $exception) {
            report($exception);

            app(ToastService::class)->show(
                'Your profile could not be saved. Please try again.',
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
     * Navigate to Settings.
     */
    public function openSettings(): void
    {
        $navigation = $this->navigate('/settings');

        if ($this->reducedMotion) {
            $navigation->transition(Transition::None);
        }
    }

    /**
     * Navigate to About.
     */
    public function openAbout(): void
    {
        $navigation = $this->navigate('/about');

        if ($this->reducedMotion) {
            $navigation->transition(Transition::None);
        }
    }

    /**
     * Retry the complete screen after a recoverable local failure.
     */
    public function retryProfile(): void
    {
        $this->loadProfile();
    }

    private function loadProfile(): void
    {
        $this->screenState = 'content';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null || $profile->onboarding_completed_at === null) {
                $this->replace('/onboarding')->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);

            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);

            $this->mapIdentity($profile);
            $this->mapEvidence($profile);
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

        $this->identityName = $normalizedName === '' ? 'Friend' : $normalizedName;
        $this->monogram = $normalizedName === ''
            ? ''
            : Str::upper(Str::substr($normalizedName, 0, 1));
        $this->memberSince = 'Training since '.$profile->created_at->format('F Y');
        $this->goalLabel = $profile->training_goal->label();
        $this->paceLabel = $profile->difficulty_preference->label();
    }

    private function mapEvidence(LocalProfile $profile): void
    {
        $overview = app(StatisticsService::class)->overview($profile);
        $achievements = app(AchievementService::class)->overview($profile);
        $unlocked = $achievements
            ->filter(fn (Achievement $achievement): bool => $achievement->unlocks->isNotEmpty())
            ->count();

        $this->workoutsLabel = (string) ($overview?->workouts_completed ?? 0);
        $this->streakLabel = (string) ($overview?->current_streak ?? 0);
        $this->achievementsLabel = $unlocked.' of '.$achievements->count();
    }
}
