@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.store-head')
    </head>

    <body class="relative min-h-screen bg-store-surface font-store text-store-ink antialiased selection:bg-store-flame selection:text-white">
        {{-- Background ambient blur gradients for rich glassmorphism refraction --}}
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-40 left-1/4 h-[500px] w-[500px] rounded-full bg-orange-200/25 blur-3xl"></div>
            <div class="absolute -top-20 right-1/4 h-[450px] w-[450px] rounded-full bg-sky-200/25 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/3 h-[600px] w-[600px] rounded-full bg-amber-100/30 blur-3xl"></div>
        </div>

        <div class="flex min-h-screen flex-col">
            <x-layouts::store.navbar />

            <main class="flex-1">
                {{ $slot }}
            </main>

            <x-layouts::store.footer />
        </div>

        @fluxScripts
    </body>
</html>

