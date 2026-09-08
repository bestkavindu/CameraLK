@php
    $links = [
        ['label' => __('Home'), 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => __('Products'), 'href' => route('store.products'), 'active' => request()->routeIs('store.products')],
        ['label' => __('Category'), 'href' => route('store.products').'#categories', 'active' => false],
    ];
@endphp

<header class="flex items-center justify-between gap-6 border-b border-store-line bg-white px-5 py-4 sm:px-8">
    <a href="{{ route('home') }}" wire:navigate class="flex items-baseline gap-2.5 no-underline hover:no-underline">
        <span class="font-store-display text-[19px] font-bold tracking-[-0.01em] text-store-ink">
            SHUTTER<span class="text-store-flame">&amp;</span>SKY
        </span>
        <span class="text-[11px] uppercase tracking-[0.14em] text-store-faint">{{ __('Colombo') }}</span>
    </a>

    <nav class="flex gap-5 text-sm font-medium sm:gap-[26px]">
        @foreach ($links as $link)
            <a
                href="{{ $link['href'] }}"
                wire:navigate
                @class([
                    'no-underline hover:no-underline',
                    'border-b-2 border-store-flame pb-0.5 text-store-ink' => $link['active'],
                    'text-store-ink-soft hover:text-store-ink' => ! $link['active'],
                ])
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</header>
