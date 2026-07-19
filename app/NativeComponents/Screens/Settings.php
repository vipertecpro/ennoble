<?php

namespace App\NativeComponents\Screens;

use App\Domain\Profile\ProfileService;
use App\Domain\Profile\ProgressResetService;
use App\Domain\Settings\SettingsService;
use App\Enums\ThemePreference;
use App\Models\Setting;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Feedback\ToastService;
use App\NativeUI\Feedback\ToastType;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\Transition;
use Throwable;

final class Settings extends NativeComponent
{
    public string $screenState = 'content';

    public string $screenError = 'Your preferences could not be loaded. Please try again.';

    public string $themePreference = ThemePreference::System->value;

    public bool $soundEnabled = true;

    public bool $hapticsEnabled = true;

    public bool $reducedMotion = false;

    public bool $reminderPlanned = false;

    public bool $resetArmed = false;

    public int $motionDuration = 0;

    /**
     * Apply the saved theme and populate controls from persisted preferences.
     */
    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();

        $this->loadSettings();
    }

    public function render(): Element
    {
        return $this->view('screens.settings');
    }

    /**
     * Supply the Settings title and purpose to native chrome.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Settings')
            ->subtitle('Preferences stay on this device')
            ->back(true);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    /**
     * Persist a valid appearance choice and repaint semantic tokens immediately.
     */
    public function updatedThemePreference(string $value): void
    {
        $preference = ThemePreference::tryFrom($value);

        if ($preference === null) {
            $this->themePreference = ThemePreference::System->value;

            return;
        }

        if ($this->persistSettings()) {
            app(ThemeManager::class)->apply($preference);
            app(HapticService::class)->trigger(HapticFeedback::Selection);
        }
    }

    /**
     * Persist the sound preference.
     */
    public function updatedSoundEnabled(): void
    {
        if ($this->persistSettings()) {
            app(HapticService::class)->trigger(HapticFeedback::Selection);
        }
    }

    /**
     * Persist the haptic preference, confirming only when haptics remain on.
     */
    public function updatedHapticsEnabled(): void
    {
        if ($this->persistSettings() && $this->hapticsEnabled) {
            app(HapticService::class)->trigger(HapticFeedback::Selection);
        }
    }

    /**
     * Persist the Reduced Motion preference and resolve authored durations.
     */
    public function updatedReducedMotion(): void
    {
        if ($this->persistSettings()) {
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Normal);

            app(HapticService::class)->trigger(HapticFeedback::Selection);
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
     * Arm the destructive reset, revealing the explicit confirmation.
     */
    public function armReset(): void
    {
        $this->resetArmed = true;
        app(HapticService::class)->trigger(HapticFeedback::Warning);
    }

    /**
     * Dismiss the reset confirmation without changing any data.
     */
    public function cancelReset(): void
    {
        $this->resetArmed = false;
    }

    /**
     * Wipe all local play evidence (stats, badges, history) for a clean slate.
     */
    public function resetProgress(): void
    {
        $this->resetArmed = false;

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null) {
                return;
            }

            app(ProgressResetService::class)->reset($profile);

            app(HapticService::class)->trigger(HapticFeedback::Success);
            app(ToastService::class)->show('Your stats and badges were reset.', ToastType::Success);
        } catch (Throwable $exception) {
            report($exception);

            app(ToastService::class)->show(
                'Your progress could not be reset. Please try again.',
                ToastType::Error,
            );
        }
    }

    /**
     * Return to the previous native screen.
     */
    public function goBack(): void
    {
        $this->back();
    }

    /**
     * Retry loading persisted preferences after a recoverable failure.
     */
    public function retrySettings(): void
    {
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $this->screenState = 'content';

        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null) {
                $this->replace('/')->transition(Transition::None);

                return;
            }

            $this->applySetting(app(SettingsService::class)->forProfile($profile));
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function applySetting(Setting $setting): void
    {
        $this->themePreference = $setting->theme_preference->value;
        $this->soundEnabled = $setting->sound_enabled;
        $this->hapticsEnabled = $setting->haptics_enabled;
        $this->reducedMotion = $setting->reduced_motion;
        $this->reminderPlanned = $setting->daily_reminder_enabled;
        $this->motionDuration = $this->reducedMotion
            ? 0
            : DesignTokens::motionDuration(MotionToken::Normal);
    }

    private function persistSettings(): bool
    {
        try {
            $profile = app(ProfileService::class)->current();

            if ($profile === null) {
                return false;
            }

            $settings = app(SettingsService::class);
            $existing = $settings->forProfile($profile);

            $saved = $settings->save(
                profile: $profile,
                themePreference: ThemePreference::from($this->themePreference),
                soundEnabled: $this->soundEnabled,
                hapticsEnabled: $this->hapticsEnabled,
                reducedMotion: $this->reducedMotion,
                dailyReminderEnabled: $existing->daily_reminder_enabled,
                accessibilityPreferences: $existing->accessibility_preferences ?? [],
            );

            $this->applySetting($saved);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            app(ToastService::class)->show(
                'Your preference could not be saved. Please try again.',
                ToastType::Error,
            );
            $this->loadSettings();

            return false;
        }
    }
}
