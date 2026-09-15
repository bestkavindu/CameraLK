@php
    /**
     * The artwork lives in public/images. SVG is preferred and wins when both
     * are present; the wordmark below is only a stand-in for when neither file
     * has been dropped in yet. Callers pass their own sizing classes.
     */
    $logo = collect(['images/logo.svg', 'images/logo.png'])
        ->first(fn (string $path): bool => is_file(public_path($path)));
@endphp

@if ($logo)
    <img
        src="{{ asset($logo) }}"
        alt="{{ config('app.name', 'Image Expo') }}"
        {{ $attributes }}
    />
@else
    <span {{ $attributes->merge(['class' => 'font-store-display font-bold leading-none tracking-[-0.015em] text-store-ink']) }}>
        IMAGE<span class="text-store-flame">EXPO</span>
    </span>
@endif
