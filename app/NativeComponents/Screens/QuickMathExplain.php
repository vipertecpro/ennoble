<?php

namespace App\NativeComponents\Screens;

use App\Domain\Games\QuickMath\QuickMathExplainer;
use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\NativeUI\Theme\ThemeManager;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;

/**
 * A chat-style, step-by-step explanation of a single Quick Math problem. The
 * assistant's messages are generated offline by QuickMathExplainer and revealed
 * one at a time — a typing indicator precedes each new bubble — so the screen
 * reads like a short tutoring conversation. Reached from the game's reveal beat;
 * closing returns to the paused game.
 */
final class QuickMathExplain extends NativeComponent
{
    public string $expression = '';

    public int $answer = 0;

    /** @var list<string> */
    public array $steps = [];

    public int $revealedCount = 0;

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();

        $this->expression = (string) $this->data('expression', '');
        $this->answer = (int) $this->data('answer', 0);
        $this->steps = app(QuickMathExplainer::class)->explain($this->expression, $this->answer);

        $profile = app(ProfileService::class)->current();

        if ($profile !== null) {
            $this->reducedMotion = app(SettingsService::class)->forProfile($profile)->reduced_motion;
        }

        $this->motionDuration = $this->reducedMotion ? 0 : DesignTokens::motionDuration(MotionToken::Normal);
        $this->revealedCount = $this->reducedMotion ? count($this->steps) : min(1, count($this->steps));
    }

    public function render(): Element
    {
        return $this->view('screens.games.quick-math.explain');
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
     * Reveal the next assistant message on a gentle cadence so each bubble is
     * preceded by the typing indicator. A no-op once every step is shown.
     */
    #[Poll(750)]
    public function revealNext(): void
    {
        if ($this->revealedCount < count($this->steps)) {
            $this->revealedCount++;
        }
    }

    public function close(): void
    {
        $this->back();
    }

    public function onBackPressed(): void
    {
        $this->back();
    }
}
