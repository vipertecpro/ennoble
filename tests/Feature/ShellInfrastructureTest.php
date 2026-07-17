<?php

use App\Domain\Profile\ProfileService;
use App\Domain\Settings\SettingsService;
use App\Enums\ThemePreference;
use App\NativeUI\Dialogs\DialogService;
use App\NativeUI\Feedback\HapticFeedback;
use App\NativeUI\Feedback\HapticService;
use App\NativeUI\Feedback\ToastService;
use App\NativeUI\Feedback\ToastType;
use Native\Mobile\Testing\Native;
use Tests\Fixtures\Native\ShellComponentPreview;

beforeEach(function () {
    app('view')->addLocation(base_path('tests/Fixtures/views'));
});

test('shared top bar action slots and loading variants render accessibly', function () {
    Native::test(ShellComponentPreview::class)
        ->assertSee('Reusable top bar')
        ->assertSee('Both action slots are available')
        ->assertSee('Loading inline')
        ->assertSee('Loading action')
        ->assertElement('pressable', fn (array $node): bool => ($node['props']['a11y_label'] ?? null) === 'Left action')
        ->assertElement('pressable', fn (array $node): bool => ($node['props']['a11y_label'] ?? null) === 'Right action')
        ->assertElement('button', fn (array $node): bool => ($node['props']['loading'] ?? false) === true)
        ->assertAccessible();
});

test('haptic service respects the saved Prompt 2 preference', function () {
    $profile = app(ProfileService::class)->createOrUpdate('Haptic Tester');
    app(SettingsService::class)->save(
        profile: $profile,
        themePreference: ThemePreference::System,
        soundEnabled: true,
        hapticsEnabled: false,
        reducedMotion: false,
        dailyReminderEnabled: false,
    );

    $bridge = Native::fakeBridge();

    expect(app(HapticService::class)->trigger(HapticFeedback::Selection))->toBeFalse();
    $bridge->assertNothingCalled();
});

test('haptic service uses the installed generic vibration bridge when enabled', function () {
    $profile = app(ProfileService::class)->createOrUpdate('Haptic Tester');
    app(SettingsService::class)->save(
        profile: $profile,
        themePreference: ThemePreference::System,
        soundEnabled: true,
        hapticsEnabled: true,
        reducedMotion: false,
        dailyReminderEnabled: false,
    );

    $bridge = Native::fakeBridge()
        ->respondTo('Device.Vibrate', ['success' => true]);

    expect(app(HapticService::class)->trigger(HapticFeedback::Success))->toBeTrue()
        ->and($bridge->callsTo('Device.Vibrate'))->toHaveCount(1);
});

test('toast service preserves its semantic type in visible text', function () {
    $bridge = Native::fakeBridge();

    app(ToastService::class)->show('Session saved', ToastType::Success);

    expect($bridge->callsTo('Dialog.Toast'))->toHaveCount(1)
        ->and($bridge->callsTo('Dialog.Toast')[0]['params'])->toBe([
            'message' => 'Success: Session saved',
            'duration' => 'short',
        ]);
});

test('dialog service creates native alert and confirmation contracts', function () {
    $bridge = Native::fakeBridge();
    $dialogs = app(DialogService::class);

    $alert = $dialogs->alert('Notice', 'Shell ready');
    $alert->show();
    $confirmation = $dialogs->confirm('Reset?', 'This is only an infrastructure test.', 'Reset');
    $confirmation->show();

    $calls = $bridge->callsTo('Dialog.Alert');

    expect($calls)->toHaveCount(2)
        ->and($calls[0]['params']['buttons'])->toBe(['OK'])
        ->and($calls[1]['params']['buttons'])->toBe([
            ['label' => 'Cancel', 'style' => 'cancel'],
            ['label' => 'Reset', 'style' => 'destructive'],
        ]);
});
