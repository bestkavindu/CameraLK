@props([
    'title' => null,

    /**
     * Float the navbar over the page instead of reserving space above it.
     * The home hero is a full-bleed photograph that the navbar sits on top
     * of; every other storefront page starts below the navbar.
     */
    'overlayNav' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.store-head')
    </head>

    <body @class([
        'relative min-h-screen font-store text-store-ink antialiased selection:bg-store-flame selection:text-white',
        'bg-store-night' => $overlayNav,
        'bg-store-surface' => ! $overlayNav,
    ])>
        {{-- Background ambient blur gradients for rich glassmorphism refraction.
             Suppressed under an overlay hero, which paints its own ground. --}}
        @unless ($overlayNav)
            <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
                <div class="absolute -top-40 left-1/4 h-[500px] w-[500px] rounded-full bg-orange-200/25 blur-3xl"></div>
                <div class="absolute -top-20 right-1/4 h-[450px] w-[450px] rounded-full bg-sky-200/25 blur-3xl"></div>
                <div class="absolute top-1/2 left-1/3 h-[600px] w-[600px] rounded-full bg-amber-100/30 blur-3xl"></div>
            </div>
        @endunless

        <div class="flex min-h-screen flex-col">
            <x-layouts::store.navbar />

            {{-- 84px clears the fixed navbar: 18px of top gutter plus a 66px pill. --}}
            <main @class(['flex-1', 'pt-[84px]' => ! $overlayNav])>
                {{ $slot }}
            </main>

            <x-layouts::store.footer />
        </div>

        @fluxScripts
    </body>
</html>
