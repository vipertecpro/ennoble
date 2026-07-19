<?php

namespace App\NativeComponents\Screens;

use App\Domain\Onboarding\OnboardingService;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Models\Profile as LocalProfile;
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

    public string $monogram = '';

    public string $identityName = 'Friend';

    public string $memberSince = '';

    public string $goalLabel = '';

    public string $paceLabel = '';

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public float $pressScale = 1.0;

    public float $pressOpacity = 1.0;

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
     * Refresh identity after returning from another native screen.
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
     * Navigate to the My Details editor.
     */
    public function openMyDetails(): void
    {
        $this->navigateWithMotion('/my-details');
    }

    /**
     * Navigate to Settings.
     */
    public function openSettings(): void
    {
        $this->navigateWithMotion('/settings');
    }

    /**
     * Navigate to About.
     */
    public function openAbout(): void
    {
        $this->navigateWithMotion('/about');
    }

    /**
     * Retry the complete screen after a recoverable local failure.
     */
    public function retryProfile(): void
    {
        $this->loadProfile();
    }

    private function navigateWithMotion(string $path): void
    {
        $navigation = $this->navigate($path);

        if ($this->reducedMotion) {
            $navigation->transition(Transition::None);
        }
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
            $this->pressScale = $this->reducedMotion ? 1.0 : 0.985;
            $this->pressOpacity = $this->reducedMotion ? 1.0 : DesignTokens::OPACITY['pressed'];

            $this->mapIdentity($profile);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function mapIdentity(LocalProfile $profile): void
    {
        $normalizedName = Str::squish($profile->display_name ?? '');

        $this->identityName = $normalizedName === '' ? 'Friend' : $normalizedName;
        $this->monogram = $normalizedName === ''
            ? ''
            : Str::upper(Str::substr($normalizedName, 0, 1));
        $this->memberSince = 'Playing since '.$profile->created_at->format('F Y');
        $this->goalLabel = $profile->training_goal->label();
        $this->paceLabel = $profile->difficulty_preference->label();
    }
}
