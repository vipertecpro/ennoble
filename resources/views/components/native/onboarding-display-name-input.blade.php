@use('App\Domain\Profile\ProfileService')

@props([
    'displayName' => '',
    'valid' => true,
])

<native:outlined-text-input
    native:model.blur="displayName"
    label="Display name (optional)"
    placeholder="Your name"
    keyboard="text"
    :max-length="ProfileService::DISPLAY_NAME_MAX_LENGTH"
    :is-error="! $valid"
    :supporting="$valid
        ? 'No email, account, or cloud profile is created.'
        : 'Use 40 characters or fewer.'"
    a11y-label="Optional display name"
/>
