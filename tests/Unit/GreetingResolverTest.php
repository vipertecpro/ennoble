<?php

use App\NativeUI\Home\GreetingResolver;
use Carbon\CarbonImmutable;

test('it resolves contextual greetings from the local hour', function (
    string $time,
    string $expectedGreeting,
) {
    $resolver = new GreetingResolver;

    expect($resolver->greeting(CarbonImmutable::parse($time)))->toBe($expectedGreeting);
})->with([
    'early morning' => ['2026-07-18 05:00:00', 'Good Morning'],
    'late morning' => ['2026-07-18 11:59:59', 'Good Morning'],
    'afternoon' => ['2026-07-18 12:00:00', 'Good Afternoon'],
    'late afternoon' => ['2026-07-18 16:59:59', 'Good Afternoon'],
    'evening' => ['2026-07-18 17:00:00', 'Good Evening'],
    'overnight' => ['2026-07-18 02:00:00', 'Good Evening'],
]);

test('it normalizes a local display name and supplies a friendly fallback', function () {
    $resolver = new GreetingResolver;

    expect($resolver->displayName('  Ada   Local  '))->toBe('Ada Local')
        ->and($resolver->displayName(null))->toBe('friend')
        ->and($resolver->displayName('   '))->toBe('friend');
});
