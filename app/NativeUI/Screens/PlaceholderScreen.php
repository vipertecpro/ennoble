<?php

namespace App\NativeUI\Screens;

use App\NativeUI\Dialogs\InteractsWithDialogs;
use App\NativeUI\Theme\ThemeManager;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\NativeComponent;

abstract class PlaceholderScreen extends NativeComponent
{
    use InteractsWithDialogs;

    protected const TITLE = '';

    protected const SUBTITLE = 'Application shell placeholder';

    public string $shellState = ShellState::Empty->value;

    public string $errorMessage = 'This placeholder could not be displayed.';

    /**
     * Apply the saved theme before the screen is rendered.
     */
    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();
    }

    /**
     * Resolve the native navigation title.
     */
    public function navTitle(): string
    {
        return static::TITLE;
    }

    /**
     * Supply reusable title, subtitle, and back-button configuration.
     */
    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title(static::TITLE)
            ->subtitle(static::SUBTITLE)
            ->back($this->showsBackButton());
    }

    /**
     * Return to the previous native screen.
     */
    public function goBack(): void
    {
        $this->back();
    }

    /**
     * Replace the current route with the shell home.
     */
    public function enterApplication(): void
    {
        $this->replace('/');
    }

    /**
     * Navigate to the settings placeholder.
     */
    public function openSettings(): void
    {
        $this->navigate('/settings');
    }

    /**
     * Navigate to the About placeholder.
     */
    public function openAbout(): void
    {
        $this->navigate('/about');
    }

    /**
     * Put the shared screen container into its loading state.
     */
    public function showLoading(): void
    {
        $this->shellState = ShellState::Loading->value;
    }

    /**
     * Put the shared screen container into a recoverable error state.
     */
    public function showError(string $message = 'Please try again.'): void
    {
        $this->errorMessage = $message;
        $this->shellState = ShellState::Error->value;
    }

    /**
     * Restore the honest placeholder empty state.
     */
    public function retry(): void
    {
        $this->shellState = ShellState::Empty->value;
    }

    /**
     * Detail placeholders opt into the native back button.
     */
    protected function showsBackButton(): bool
    {
        return false;
    }
}
