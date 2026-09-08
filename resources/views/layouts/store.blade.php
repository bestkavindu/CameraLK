@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.store-head')
    </head>

    <body class="min-h-screen bg-store-surface font-store text-store-ink antialiased">
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
