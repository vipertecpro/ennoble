@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'slug',
    'hero' => false,
    'motionDuration' => 0,
    'animated' => false,
])

@php
    // Games with a bundled Lottie animation (resources/animations/<slug>.json).
    $hasAnimation = in_array($slug, ['word-match', 'quick-math'], true);
@endphp

@if ($animated && $hasAnimation)
    {{-- Real native Lottie animation (lottie-ios / lottie-android), looping.
         Sits on a light tile so the colorful glyphs read in any theme. --}}
    {{-- No tile: the animation stands on its own, larger. --}}
    <native:column
        class="{{ $hero ? 'h-40 w-full' : 'w-24 h-24' }}"
        :animate-duration="$motionDuration"
    >
        <native:lottie-player
            :source="$slug"
            loop
            class="flex-1 w-full"
            alt="Animated {{ $slug }} icon"
        />
    </native:column>
@else
    @php
        [$ios, $android, $label] = match ($slug) {
            'word-match' => [Ios::TextformatAbc, AndroidOutlined::Abc, 'Abstract word matching illustration'],
            'quick-math' => [Ios::NumberSquare, AndroidOutlined::Numbers, 'Abstract quick math illustration'],
            'recall' => [Ios::Grid, AndroidOutlined::GridView, 'Abstract memory recall illustration'],
            default => [Ios::Gamecontroller, AndroidOutlined::SportsEsports, 'Abstract game illustration'],
        };
    @endphp

    <native:column
        class="{{ $hero ? 'h-40' : 'w-16 h-16' }} items-center justify-center rounded-2xl bg-theme-primary-surface"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    >
        <native:column class="{{ $hero ? 'w-24 h-24' : 'w-12 h-12' }} items-center justify-center rounded-full bg-theme-surface-elevated shadow-sm">
            <x-native.ui.icon
                :ios="$ios"
                :android="$android"
                :size="$hero ? 48 : 28"
                :a11y-label="$label"
            />
        </native:column>
    </native:column>
@endif
