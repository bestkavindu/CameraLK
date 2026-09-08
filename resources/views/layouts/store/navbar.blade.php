@php
    $categories = \App\Models\Category::query()
        ->withCount('subCategories')
        ->orderBy('name')
        ->get();

    $links = [
        ['label' => __('Home'), 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => __('All Gear'), 'href' => route('store.products'), 'active' => request()->routeIs('store.products') && ! request()->has('category')],
    ];
@endphp

<header 
    x-data="{
        categoryOpen: false,
        mobileOpen: false,
        searchFocused: false,
        scrolled: false,
        activeCategory: null,
        init() {
            window.addEventListener('scroll', () => {
                this.scrolled = window.scrollY > 20;
            });
            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    const input = document.getElementById('navbar-quick-search');
                    if (input) input.focus();
                }
            });
        }
    }"
    class="sticky top-2 z-40 w-full px-3 transition-all duration-300 sm:top-3.5 sm:px-6 lg:px-8"
>
    {{-- Ambient light refraction aura behind navbar --}}
    <div class="pointer-events-none absolute -top-10 left-1/2 -z-10 h-24 w-3/4 -translate-x-1/2 rounded-full bg-gradient-to-r from-orange-400/10 via-amber-300/15 to-sky-400/10 blur-2xl"></div>

    {{-- Main Glass Pill Capsule --}}
    <div 
        class="liquid-sheen glass-panel mx-auto flex max-w-[1360px] items-center justify-between gap-3 rounded-2xl p-2 sm:rounded-full sm:px-5 sm:py-2.5 transition-all duration-300"
        :class="scrolled ? 'shadow-[0_20px_45px_-12px_rgba(20,24,29,0.12),0_1px_3px_rgba(20,24,29,0.05)] bg-white/85' : 'shadow-[0_15px_35px_-10px_rgba(20,24,29,0.06),0_1px_2px_rgba(20,24,29,0.03)] bg-white/70'"
    >
        {{-- Brand / Logo --}}
        <div class="flex items-center gap-3">
            <a 
                href="{{ route('home') }}" 
                wire:navigate 
                class="group flex items-center gap-2.5 no-underline transition-transform duration-200 active:scale-95"
            >
                {{-- Shutter aperture liquid glass badge --}}
                <div class="relative flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-store-flame via-orange-500 to-amber-500 text-white shadow-[0_4px_12px_rgba(228,87,46,0.35)] ring-1 ring-white/60 transition-all duration-300 group-hover:scale-105 group-hover:shadow-[0_6px_18px_rgba(228,87,46,0.45)] sm:size-10">
                    <svg class="size-5 transition-transform duration-500 group-hover:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.5"/>
                        <path d="m14.31 8 5.74 9.94"/>
                        <path d="M9.69 8h11.48"/>
                        <path d="m7.38 12 5.74-9.94"/>
                        <path d="M9.69 16 3.95 6.06"/>
                        <path d="M14.31 16H2.83"/>
                        <path d="m16.62 12-5.74 9.94"/>
                    </svg>
                    {{-- Lens specular gloss spot --}}
                    <span class="absolute top-1 left-1 size-1.5 rounded-full bg-white/80 blur-[0.5px]"></span>
                </div>

                {{-- Brand text & Hub Tag --}}
                <div class="flex flex-col">
                    <div class="flex items-baseline gap-1.5">
                        <span class="font-store-display text-base font-extrabold tracking-tight text-store-ink sm:text-[19px]">
                            SHUTTER<span class="bg-gradient-to-r from-store-flame to-orange-500 bg-clip-text text-transparent">&amp;</span>SKY
                        </span>
                    </div>
                    <div class="hidden items-center gap-1.5 text-[10px] uppercase font-semibold tracking-wider text-store-faint sm:flex">
                        <span class="relative flex size-1.5">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex size-1.5 rounded-full bg-emerald-500"></span>
                        </span>
                        <span>{{ __('Colombo Hub') }}</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- Desktop Navigation Links --}}
        <nav class="hidden items-center gap-1 md:flex lg:gap-1.5">
            {{-- Standard Links --}}
            @foreach ($links as $link)
                <a
                    href="{{ $link['href'] }}"
                    wire:navigate
                    @class([
                        'relative px-3.5 py-1.5 text-xs font-semibold rounded-full transition-all duration-200 no-underline',
                        'bg-store-ink text-white shadow-xs' => $link['active'],
                        'text-store-ink-soft hover:text-store-ink hover:bg-white/80' => ! $link['active'],
                    ])
                >
                    {{ $link['label'] }}
                </a>
            @endforeach

            {{-- Categories Dropdown Trigger --}}
            <div 
                class="relative"
                @mouseenter="categoryOpen = true"
                @mouseleave="categoryOpen = false"
            >
                <button
                    type="button"
                    @click="categoryOpen = !categoryOpen"
                    :class="categoryOpen ? 'bg-white text-store-ink shadow-xs ring-1 ring-store-line' : 'text-store-ink-soft hover:text-store-ink hover:bg-white/80'"
                    class="flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all duration-200 cursor-pointer"
                    aria-expanded="false"
                    :aria-expanded="categoryOpen.toString()"
                >
                    <span>{{ __('Categories') }}</span>
                    <svg 
                        class="size-3.5 text-store-muted transition-transform duration-200"
                        :class="categoryOpen ? 'rotate-180 text-store-flame' : ''"
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2.2"
                    >
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>

                {{-- Liquid Glass Category Mega-Flyout --}}
                <div
                    x-show="categoryOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2 scale-98"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-2 scale-98"
                    class="glass-dropdown absolute left-1/2 top-full z-50 mt-3 w-[560px] -translate-x-1/2 rounded-2xl p-4 shadow-2xl"
                >
                    {{-- Dropdown Header --}}
                    <div class="mb-3 flex items-center justify-between border-b border-store-line/60 pb-2.5 px-2">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-store-flame"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-store-ink">{{ __('Browse by Gear Category') }}</span>
                        </div>
                        <a 
                            href="{{ route('store.products') }}" 
                            wire:navigate
                            class="text-[11px] font-semibold text-store-flame hover:underline no-underline"
                        >
                            {{ __('View all gear') }} &rarr;
                        </a>
                    </div>

                    {{-- Dynamic Categories Grid --}}
                    <div class="grid grid-cols-2 gap-2">
                        @forelse ($categories as $cat)
                            <a
                                href="{{ route('store.products', ['category' => $cat->slug]) }}"
                                wire:navigate
                                class="group flex items-start gap-3 rounded-xl p-2.5 transition-all duration-150 hover:bg-white/90 hover:shadow-xs no-underline"
                            >
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-store-wash text-store-ink transition-colors group-hover:bg-store-flame group-hover:text-white">
                                    @php
                                        $nameLower = strtolower($cat->name);
                                    @endphp
                                    @if (str_contains($nameLower, 'drone'))
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/>
                                            <path d="M3 6l4.5 4.5"/>
                                            <path d="M21 6l-4.5 4.5"/>
                                            <path d="M3 18l4.5 -4.5"/>
                                            <path d="M21 18l-4.5 -4.5"/>
                                        </svg>
                                    @elseif (str_contains($nameLower, 'lens'))
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="9"/>
                                            <circle cx="12" cy="12" r="5"/>
                                            <circle cx="12" cy="12" r="2"/>
                                        </svg>
                                    @elseif (str_contains($nameLower, 'gimbal') || str_contains($nameLower, 'stabilizer'))
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="6" y="4" width="12" height="16" rx="2"/>
                                            <line x1="12" y1="2" x2="12" y2="4"/>
                                            <line x1="12" y1="20" x2="12" y2="22"/>
                                        </svg>
                                    @elseif (str_contains($nameLower, 'accessor'))
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/>
                                            <path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/>
                                            <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
                                        </svg>
                                    @else
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                            <circle cx="12" cy="13" r="3"/>
                                        </svg>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-store-ink group-hover:text-store-flame">
                                            {{ $cat->name }}
                                        </span>
                                        @if ($cat->sub_categories_count > 0)
                                            <span class="rounded-full bg-store-wash/80 px-1.5 py-0.5 text-[10px] font-semibold text-store-muted">
                                                {{ $cat->sub_categories_count }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 truncate text-[11px] text-store-muted">
                                        {{ __('Explore') }} {{ $cat->name }} {{ __('collection') }}
                                    </p>
                                </div>
                            </a>
                        @empty
                            <a
                                href="{{ route('store.products') }}"
                                wire:navigate
                                class="col-span-2 text-center text-xs text-store-muted py-2"
                            >
                                {{ __('All gear available') }}
                            </a>
                        @endforelse
                    </div>

                    {{-- Featured Bottom Bar --}}
                    <div class="mt-3 flex items-center justify-between rounded-xl bg-gradient-to-r from-orange-500/10 via-amber-500/10 to-transparent p-2.5 border border-orange-200/40">
                        <div class="flex items-center gap-2">
                            <span class="flex size-6 items-center justify-center rounded-full bg-store-flame text-white text-[11px] font-bold">★</span>
                            <div class="text-[11px] font-medium text-store-ink">
                                <span class="font-bold text-store-flame">{{ __('2-Year Warranty') }}</span> {{ __('on all brand items.') }}
                            </div>
                        </div>
                        <a 
                            href="{{ route('store.products') }}"
                            wire:navigate 
                            class="text-[11px] font-semibold text-store-ink hover:text-store-flame no-underline"
                        >
                            {{ __('Shop Deals') }} &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- Hot Deals Link --}}
            <a
                href="{{ route('store.products') }}"
                wire:navigate
                class="group flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold text-store-ink-soft hover:text-store-ink hover:bg-white/80 transition-all duration-200 no-underline"
            >
                <svg class="size-3.5 text-store-flame transition-transform duration-300 group-hover:scale-110" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12.89 2.14a1 1 0 0 0-1.28.16C8.89 4.9 6 8.57 6 12.5 6 16.08 8.92 19 12.5 19s6.5-2.92 6.5-6.5c0-1.46-.43-2.88-1.22-4.1-.3-.46-.86-.68-1.38-.54-.53.14-.88.6-.88 1.14 0 1.25-.66 2.33-1.63 2.92a.75.75 0 0 1-1.12-.66c0-.98.39-1.92 1.12-2.61.42-.4.65-.96.63-1.54-.03-.58-.32-1.11-.79-1.45l-.84-.53z"/>
                </svg>
                <span>{{ __('Deals') }}</span>
                <span class="rounded-full bg-store-flame/10 px-1.5 py-0.2 text-[9px] font-extrabold text-store-flame">HOT</span>
            </a>
        </nav>

        {{-- Right Section: Quick Search, Auth & Mobile Menu --}}
        <div class="flex items-center gap-2">
            {{-- Desktop Quick Search Bar --}}
            <form 
                action="{{ route('store.products') }}" 
                method="GET" 
                class="relative hidden lg:flex items-center"
            >
                <div class="relative flex items-center">
                    <input
                        id="navbar-quick-search"
                        type="search"
                        name="q"
                        placeholder="{{ __('Search cameras, lenses…') }}"
                        class="glass-search h-8.5 w-44 rounded-full pl-8 pr-12 text-xs text-store-ink placeholder:text-store-faint transition-all duration-300 focus:w-60 focus:bg-white focus:outline-none focus:ring-2 focus:ring-store-flame/20 xl:w-52 [&::-webkit-search-cancel-button]:hidden"
                    />
                    <svg class="pointer-events-none absolute left-2.5 size-3.5 text-store-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <kbd class="pointer-events-none absolute right-2.5 rounded border border-store-line bg-white/80 px-1 py-0.5 font-mono text-[9px] font-semibold text-store-faint shadow-2xs">⌘K</kbd>
                </div>
            </form>

            {{-- Support / Warranty Trust Tag (Desktop) --}}
            <a 
                href="https://wa.me/94770000000" 
                target="_blank" 
                rel="noopener noreferrer"
                class="hidden items-center gap-1.5 rounded-full border border-store-line/60 bg-white/40 px-3 py-1.5 text-xs font-semibold text-store-ink-soft backdrop-blur-md transition-all duration-200 hover:bg-white hover:text-store-ink hover:shadow-2xs sm:flex no-underline"
                title="{{ __('WhatsApp Direct Support') }}"
            >
                <svg class="size-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <span>{{ __('Support') }}</span>
            </a>

            {{-- Auth / Dashboard Action --}}
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="group flex items-center gap-2 rounded-full border border-store-line bg-store-ink px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs transition-all duration-200 hover:bg-neutral-800 no-underline"
                >
                    <span class="flex size-4 items-center justify-center rounded-full bg-store-flame text-[9px] font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </span>
                    <span class="hidden sm:inline">{{ __('Dashboard') }}</span>
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    wire:navigate
                    class="flex items-center gap-1.5 rounded-full border border-store-line/80 bg-white/70 px-3.5 py-1.5 text-xs font-semibold text-store-ink shadow-2xs backdrop-blur-md transition-all duration-200 hover:bg-white hover:border-store-line hover:shadow-xs no-underline"
                >
                    <svg class="size-3.5 text-store-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>{{ __('Sign in') }}</span>
                </a>
            @endauth

            {{-- Mobile Hamburger Trigger --}}
            <button
                type="button"
                @click="mobileOpen = !mobileOpen"
                class="flex size-9 items-center justify-center rounded-xl border border-store-line bg-white/80 text-store-ink backdrop-blur-md transition-all duration-200 hover:bg-white md:hidden cursor-pointer"
                aria-label="{{ __('Toggle Menu') }}"
            >
                <div class="relative size-4">
                    <span 
                        class="absolute left-0 top-0.5 h-0.5 w-4 rounded-full bg-store-ink transition-all duration-300"
                        :class="mobileOpen ? 'rotate-45 translate-y-1.5' : ''"
                    ></span>
                    <span 
                        class="absolute left-0 top-1.5 h-0.5 w-4 rounded-full bg-store-ink transition-opacity duration-200"
                        :class="mobileOpen ? 'opacity-0' : 'opacity-100'"
                    ></span>
                    <span 
                        class="absolute left-0 top-2.5 h-0.5 w-4 rounded-full bg-store-ink transition-all duration-300"
                        :class="mobileOpen ? '-rotate-45 -translate-y-0.5' : ''"
                    ></span>
                </div>
            </button>
        </div>
    </div>

    {{-- Mobile Liquid Glass Drawer / Menu --}}
    <div
        x-show="mobileOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 -translate-y-3 scale-98"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-3 scale-98"
        class="glass-dropdown mx-auto mt-2.5 max-w-[1360px] rounded-2xl p-4 shadow-2xl md:hidden"
    >
        {{-- Mobile Search Box --}}
        <form action="{{ route('store.products') }}" method="GET" class="mb-3">
            <div class="relative">
                <input
                    type="search"
                    name="q"
                    placeholder="{{ __('Search cameras, lenses, drones…') }}"
                    class="h-10 w-full rounded-xl border border-store-line bg-white/80 pl-9 pr-4 text-sm text-store-ink placeholder:text-store-faint focus:border-store-flame focus:bg-white focus:outline-none focus:ring-2 focus:ring-store-flame/20"
                />
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-store-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
        </form>

        {{-- Mobile Primary Links --}}
        <div class="space-y-1 border-b border-store-line/60 pb-3">
            <a
                href="{{ route('home') }}"
                wire:navigate
                @click="mobileOpen = false"
                @class([
                    'flex items-center justify-between rounded-xl px-3 py-2 text-sm font-bold no-underline',
                    'bg-store-ink text-white' => request()->routeIs('home'),
                    'text-store-ink hover:bg-white/80' => ! request()->routeIs('home'),
                ])
            >
                <span>{{ __('Home') }}</span>
                <svg class="size-4 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a
                href="{{ route('store.products') }}"
                wire:navigate
                @click="mobileOpen = false"
                @class([
                    'flex items-center justify-between rounded-xl px-3 py-2 text-sm font-bold no-underline',
                    'bg-store-ink text-white' => request()->routeIs('store.products') && ! request()->has('category'),
                    'text-store-ink hover:bg-white/80' => ! (request()->routeIs('store.products') && ! request()->has('category')),
                ])
            >
                <span>{{ __('All Gear & Products') }}</span>
                <span class="rounded-full bg-store-wash px-2 py-0.5 text-xs text-store-muted">{{ __('Browse') }}</span>
            </a>
        </div>

        {{-- Mobile Categories Section --}}
        <div class="py-3">
            <div class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-store-faint">
                {{ __('Popular Categories') }}
            </div>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($categories as $cat)
                    <a
                        href="{{ route('store.products', ['category' => $cat->slug]) }}"
                        wire:navigate
                        @click="mobileOpen = false"
                        class="flex items-center gap-2 rounded-xl bg-white/60 p-2 text-xs font-semibold text-store-ink transition-colors hover:bg-white hover:text-store-flame no-underline"
                    >
                        <span class="size-1.5 rounded-full bg-store-flame"></span>
                        <span class="truncate">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Mobile Footer Trust & Contact --}}
        <div class="mt-2 flex items-center justify-between rounded-xl bg-store-wash/60 p-3 text-xs text-store-muted">
            <div class="flex items-center gap-1.5">
                <svg class="size-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span class="font-medium">{{ __('2-Yr Local Warranty') }}</span>
            </div>
            <a 
                href="https://wa.me/94770000000" 
                target="_blank" 
                class="font-bold text-store-flame hover:underline no-underline"
            >
                {{ __('WhatsApp Direct') }} &rarr;
            </a>
        </div>
    </div>
</header>
