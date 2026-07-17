<?php

namespace App\NativeComponents\Screens;

use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
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

final class WorkoutComplete extends NativeComponent
{
    public string $screenState = 'content';

    public string $errorMessage = 'This workout summary is not available yet.';

    public ?int $workoutId = null;

    public string $duration = 'Under 1 min';

    public int $gamesCompleted = 0;

    /**
     * @var list<string>
     */
    public array $skills = [];

    public string $scoreSummary = 'Not recorded';

    public string $accuracySummary = 'Not recorded';

    public bool $reducedMotion = false;

    public int $motionDuration = 0;

    public function mount(): void
    {
        app(ThemeManager::class)->applyCurrent();
        $this->workoutId = (int) $this->param('workout');
        $this->loadWorkout();
    }

    public function render(): Element
    {
        return $this->view('screens.workout-complete');
    }

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()
            ->title('Workout Complete')
            ->subtitle('Daily Momentum')
            ->back(false);
    }

    public function tabBarOptions(): ?TabBarOptions
    {
        return TabBarOptions::make()->hidden();
    }

    public function continueHome(): void
    {
        $this->replace('/')->transition(
            $this->reducedMotion ? Transition::None : Transition::Fade,
        );
    }

    public function onBackPressed(): void
    {
        $this->continueHome();
    }

    private function loadWorkout(): void
    {
        try {
            $profile = app(ProfileService::class)->current();
            $workout = $this->workout($profile?->getKey());

            if ($profile === null || $workout === null) {
                $this->screenState = 'error';

                return;
            }

            if ($workout->status !== WorkoutStatus::Completed) {
                $this->replace('/workout')->transition(Transition::None);

                return;
            }

            $settings = app(SettingsService::class)->forProfile($profile);
            $summary = $workout->summary ?? [];

            $this->duration = $this->formatDuration($workout->training_seconds);
            $this->gamesCompleted = $workout->items
                ->where('status', WorkoutStatus::Completed)
                ->count();
            $this->skills = $workout->items
                ->flatMap(fn ($item): array => $item->game->skill_keys)
                ->unique()
                ->map(fn (string $skill): string => Str::headline($skill))
                ->values()
                ->all();
            $this->scoreSummary = data_get($summary, 'score') === null
                ? 'Not recorded'
                : (string) data_get($summary, 'score');
            $this->accuracySummary = data_get($summary, 'accuracy') === null
                ? 'Not recorded'
                : data_get($summary, 'accuracy').'%';
            $this->reducedMotion = $settings->reduced_motion;
            $this->motionDuration = $this->reducedMotion
                ? 0
                : DesignTokens::motionDuration(MotionToken::Success);
        } catch (Throwable $exception) {
            report($exception);

            $this->screenState = 'error';
        }
    }

    private function workout(?int $profileId): ?DailyWorkout
    {
        if ($profileId === null || $this->workoutId === null) {
            return null;
        }

        return DailyWorkout::query()
            ->whereKey($this->workoutId)
            ->where('profile_id', $profileId)
            ->with(['items.game', 'items.level', 'items.sessions'])
            ->first();
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds === 0 ? 'Under 1 min' : $seconds.' sec';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return $remainingSeconds === 0
            ? $minutes.' min'
            : $minutes.' min '.$remainingSeconds.' sec';
    }
}
