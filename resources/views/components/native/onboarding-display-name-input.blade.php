@use('App\Domain\Profile\ProfileService')

@props([
    'displayName' => '',
    'overlong' => false,
    'supporting' => 'No email, account, or cloud profile is created.',
    'a11yHint' => 'Stays on this device.',
])

<native:outlined-text-input
    native:model.live.debounce.300ms="displayName"
    label="Your name"
    placeholder="e.g. Alex"
    keyboard="text"
    :max-length="ProfileService::DISPLAY_NAME_MAX_LENGTH"
    :is-error="$overlong"
    :supporting="$overlong ? 'Use 40 characters or fewer.' : $supporting"
    a11y-label="Your name"
    :a11y-hint="$a11yHint"
/>
