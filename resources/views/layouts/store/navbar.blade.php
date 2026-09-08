@php
    $categories = \App\Models\Category::query()
        ->withCount('subCategories')
        ->orderBy('name')
        ->get();

    $productCount = \App\Models\Product::query()->count();

    /**
     * Newest arrivals previewed under All Gear. There is no product detail
     * route, so each row deep-links into the grid's own search filter, which
     * matches on products.name.
     */
    $featured = \App\Models\Product::query()
        ->with(['brand:id,name', 'subCategory:id,name', 'images'])
        ->latest()
        ->take(6)
        ->get()
        ->map(function (\App\Models\Product $product): array {
            $price = (float) $product->effectivePrice();
            $off = $product->discountPercentage();

            return [
                'id' => $product->id,
                'eyebrow' => $product->brand?->name ?? $product->subCategory->name,
                'name' => $product->name,
                'alt' => trim(($product->brand?->name ?? '').' '.$product->name),
                'image' => $product->primaryImage()?->imageUrl(),
                'price' => 'Rs '.number_format($price, fmod($price, 1.0) === 0.0 ? 0 : 2),
                'off' => $off !== null && $off > 0 ? '-'.$off.'%' : null,
                'href' => route('store.products', ['q' => $product->name]),
            ];
        });

    $gearActive = request()->routeIs('store.products') && ! request()->has('category');

    $links = [
        ['label' => __('Home'), 'href' => route('home'), 'active' => request()->routeIs('home')],
    ];
@endphp

<header
    x-data="{
        categoryOpen: false,
        gearOpen: false,
        mobileOpen: false,
        scrolled: false,
        init() {
            window.addEventListener('scroll', () => {
                this.scrolled = window.scrollY > 20;
            }, { passive: true });

            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    const input = document.getElementById('navbar-quick-search');
                    if (input) input.focus();
                }
            });
        }
    }"
    class="pointer-events-none fixed inset-x-0 top-0 z-40 px-3 pt-[18px] sm:px-[clamp(16px,3vw,44px)]"
>
    <div class="pointer-events-auto mx-auto w-full max-w-[1440px]">
        {{-- Main pill. Warm white so it reads as a lit object on the hero photograph. --}}
        <div
            class="flex h-[66px] items-center gap-[clamp(14px,2vw,30px)] rounded-full border border-[rgba(20,24,29,0.07)] bg-[linear-gradient(100deg,#FFFFFF_0%,#FFF8F1_46%,#FFFFFF_100%)] pr-2.5 pl-3 text-store-ink transition-shadow sm:pr-3 sm:pl-3.5 duration-300"
            :class="scrolled
                ? 'shadow-[0_22px_50px_-18px_rgba(0,0,0,0.62),inset_0_1px_0_#FFFFFF]'
                : 'shadow-[0_18px_44px_-18px_rgba(0,0,0,0.55),inset_0_1px_0_#FFFFFF]'"
        >
            {{-- Brand --}}
            <a
                href="{{ route('home') }}"
                wire:navigate
                class="group flex shrink-0 items-center gap-[11px] text-store-ink no-underline"
            >
                {{-- Reticle badge --}}
                <span class="grid size-[34px] shrink-0 place-items-center rounded-xl sm:size-[38px] bg-[linear-gradient(155deg,#FF8A55,#E4572E)] shadow-[0_6px_14px_-4px_rgba(228,87,46,0.6),inset_0_1px_0_rgba(255,255,255,0.45)] transition-transform duration-300 group-hover:scale-105">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.7" stroke-linecap="round" class="transition-transform duration-500 group-hover:rotate-45">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 3v7M21 12h-7M12 21v-7M3 12h7"/>
                    </svg>
                </span>

                <span class="hidden flex-col gap-0.5 min-[360px]:flex">
                    <span class="text-[16px] font-bold leading-none tracking-[-0.015em] sm:text-[18px]">
                        SHUTTER<span class="text-store-flame">&amp;</span>SKY
                    </span>
                    <span class="hidden items-center gap-[5px] text-[9.5px] font-semibold uppercase tracking-[0.14em] text-store-slate sm:inline-flex">
                        <span class="size-1.5 rounded-full bg-store-go"></span>
                        <span>{{ __('Colombo hub') }}</span>
                    </span>
                </span>
            </a>

            {{-- Primary navigation --}}
            <nav class="hidden items-center gap-1 text-[14px] font-medium text-store-ink-soft min-[1000px]:flex">
                @foreach ($links as $link)
                    <a
                        href="{{ $link['href'] }}"
                        wire:navigate
                        @class([
                            'inline-flex h-[38px] items-center rounded-full no-underline',
                            'bg-store-ink px-[19px] font-semibold text-white transition-transform duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.03]' => $link['active'],
                            'px-[15px] text-store-ink-soft transition-colors duration-200 hover:bg-[rgba(20,24,29,0.06)] hover:text-store-ink' => ! $link['active'],
                        ])
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach

                {{-- All Gear. Stays a plain link to the grid; the flyout is a
                     preview of what is newest, opened on hover. --}}
                <div
                    class="relative"
                    @mouseenter="gearOpen = true"
                    @mouseleave="gearOpen = false"
                >
                    <a
                        href="{{ route('store.products') }}"
                        wire:navigate
                        @class([
                            'inline-flex h-[38px] items-center gap-[7px] rounded-full no-underline',
                            'bg-store-ink px-[19px] font-semibold text-white transition-transform duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.03]' => $gearActive,
                            'px-[15px] text-store-ink-soft transition-colors duration-200 hover:bg-[rgba(20,24,29,0.06)] hover:text-store-ink' => ! $gearActive,
                        ])
                    >
                        <span>{{ __('All Gear') }}</span>
                        @if ($featured->isNotEmpty())
                            <svg
                                width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"
                                class="transition-transform duration-200"
                                :class="gearOpen ? 'rotate-180 text-store-flame' : ''"
                            >
                                <path d="M5 9l7 7 7-7"/>
                            </svg>
                        @endif
                    </a>

                    @if ($featured->isNotEmpty())
                        <div
                            x-show="gearOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.98]"
                            class="absolute left-0 top-full z-50 mt-3 w-[620px] rounded-3xl border border-[rgba(20,24,29,0.07)] bg-white p-4 shadow-[0_30px_70px_-20px_rgba(0,0,0,0.45)]"
                        >
                            <div class="mb-3 flex items-center justify-between border-b border-[rgba(20,24,29,0.07)] px-2 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="size-1.5 rounded-full bg-store-flame"></span>
                                    <span class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-store-ink">
                                        {{ __('New in stock') }}
                                    </span>
                                </div>
                                <a
                                    href="{{ route('store.products') }}"
                                    wire:navigate
                                    class="text-[11px] font-semibold text-store-flame no-underline hover:underline"
                                >
                                    {{ trans_choice('View all :count product|View all :count products', $productCount, ['count' => $productCount]) }} &rarr;
                                </a>
                            </div>

                            <div class="grid grid-cols-3 gap-1">
                                @foreach ($featured as $item)
                                    <a
                                        href="{{ $item['href'] }}"
                                        wire:navigate
                                        class="group flex flex-col gap-1.5 rounded-2xl p-2 no-underline transition-colors duration-150 hover:bg-store-chalk"
                                    >
                                        <span class="relative block aspect-[4/3] overflow-hidden rounded-xl bg-store-wash">
                                            @if ($item['image'])
                                                <img
                                                    src="{{ $item['image'] }}"
                                                    alt="{{ $item['alt'] }}"
                                                    loading="lazy"
                                                    class="absolute inset-0 size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                />
                                            @else
                                                <span class="absolute inset-0 flex items-center justify-center p-2 text-center text-[10px] font-medium text-store-faint">
                                                    {{ $item['alt'] }}
                                                </span>
                                            @endif

                                            @if ($item['off'])
                                                <span class="absolute left-1.5 top-1.5 rounded-md bg-store-flame px-1.5 py-0.5 text-[9.5px] font-bold text-white">
                                                    {{ $item['off'] }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="truncate text-[9.5px] uppercase tracking-[0.12em] text-store-faint">
                                            {{ $item['eyebrow'] }}
                                        </span>
                                        <span class="line-clamp-2 text-[12px] font-semibold leading-[1.35] text-store-ink group-hover:text-store-flame">
                                            {{ $item['name'] }}
                                        </span>
                                        <span class="text-[12px] font-bold text-store-ink">
                                            {{ $item['price'] }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3 rounded-2xl bg-store-chalk px-3 py-2.5">
                                <span class="text-[11px] text-store-slate">{{ __('Jump straight to') }}</span>
                                <span class="flex items-center gap-3">
                                    <a
                                        href="{{ route('store.products', ['sort' => 'discount']) }}"
                                        wire:navigate
                                        class="text-[11px] font-semibold text-store-ink no-underline hover:text-store-flame"
                                    >
                                        {{ __('Biggest discount') }}
                                    </a>
                                    <span class="h-3 w-px bg-[rgba(20,24,29,0.12)]"></span>
                                    <a
                                        href="{{ route('store.products', ['sort' => 'price-asc']) }}"
                                        wire:navigate
                                        class="text-[11px] font-semibold text-store-ink no-underline hover:text-store-flame"
                                    >
                                        {{ __('Lowest price') }}
                                    </a>
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Categories flyout --}}
                <div
                    class="relative"
                    @mouseenter="categoryOpen = true"
                    @mouseleave="categoryOpen = false"
                >
                    <button
                        type="button"
                        @click="categoryOpen = ! categoryOpen"
                        :class="categoryOpen ? 'bg-[rgba(20,24,29,0.06)] text-store-ink' : 'text-store-ink-soft'"
                        class="inline-flex h-[38px] cursor-pointer items-center gap-[7px] rounded-full px-[15px] transition-colors duration-200 hover:bg-[rgba(20,24,29,0.06)] hover:text-store-ink"
                        :aria-expanded="categoryOpen.toString()"
                    >
                        <span>{{ __('Categories') }}</span>
                        <svg
                            width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"
                            class="transition-transform duration-200"
                            :class="categoryOpen ? 'rotate-180 text-store-flame' : ''"
                        >
                            <path d="M5 9l7 7 7-7"/>
                        </svg>
                    </button>

                    <div
                        x-show="categoryOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.98]"
                        class="absolute left-1/2 top-full z-50 mt-3 w-[560px] -translate-x-1/2 rounded-3xl border border-[rgba(20,24,29,0.07)] bg-white p-4 shadow-[0_30px_70px_-20px_rgba(0,0,0,0.45)]"
                    >
                        <div class="mb-3 flex items-center justify-between border-b border-[rgba(20,24,29,0.07)] px-2 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="size-1.5 rounded-full bg-store-flame"></span>
                                <span class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-store-ink">
                                    {{ __('Browse by gear category') }}
                                </span>
                            </div>
                            <a
                                href="{{ route('store.products') }}"
                                wire:navigate
                                class="text-[11px] font-semibold text-store-flame no-underline hover:underline"
                            >
                                {{ __('View all gear') }} &rarr;
                            </a>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            @forelse ($categories as $cat)
                                <a
                                    href="{{ route('store.products', ['category' => $cat->slug]) }}"
                                    wire:navigate
                                    class="group flex items-start gap-3 rounded-2xl p-2.5 no-underline transition-colors duration-150 hover:bg-store-chalk"
                                >
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-store-chalk text-store-ink transition-colors group-hover:bg-store-flame group-hover:text-white">
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
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center justify-between">
                                            <span class="text-[13px] font-semibold text-store-ink group-hover:text-store-flame">
                                                {{ $cat->name }}
                                            </span>
                                            @if ($cat->sub_categories_count > 0)
                                                <span class="rounded-full bg-store-chalk px-1.5 py-0.5 text-[10px] font-semibold text-store-slate">
                                                    {{ $cat->sub_categories_count }}
                                                </span>
                                            @endif
                                        </span>
                                        <span class="mt-0.5 block truncate text-[11px] text-store-slate">
                                            {{ __('Explore') }} {{ $cat->name }} {{ __('collection') }}
                                        </span>
                                    </span>
                                </a>
                            @empty
                                <a
                                    href="{{ route('store.products') }}"
                                    wire:navigate
                                    class="col-span-2 py-2 text-center text-[13px] text-store-slate no-underline"
                                >
                                    {{ __('All gear available') }}
                                </a>
                            @endforelse
                        </div>

                        <div class="mt-3 flex items-center justify-between rounded-2xl border border-[rgba(228,87,46,0.18)] bg-[linear-gradient(90deg,rgba(228,87,46,0.09),transparent)] p-2.5">
                            <div class="flex items-center gap-2">
                                <span class="flex size-6 items-center justify-center rounded-full bg-store-flame text-[11px] font-bold text-white">&#9733;</span>
                                <span class="text-[11px] text-store-ink">
                                    <span class="font-bold text-store-flame">{{ __('2-Year Warranty') }}</span>
                                    {{ __('on all brand items.') }}
                                </span>
                            </div>
                            <a
                                href="{{ route('store.products') }}"
                                wire:navigate
                                class="text-[11px] font-semibold text-store-ink no-underline hover:text-store-flame"
                            >
                                {{ __('Shop deals') }} &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Deals --}}
                <a
                    href="{{ route('store.products') }}"
                    wire:navigate
                    class="inline-flex h-[38px] items-center gap-2 rounded-full px-3.5 font-semibold text-store-ink no-underline transition-colors duration-200 hover:bg-[rgba(228,87,46,0.09)]"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#E4572E">
                        <path d="M13 2s1 3.2-1.6 6C9 10.5 7 12.2 7 15a5 5 0 0 0 10 0c0-2-1-3.4-1-3.4s2.5 1.2 2.5 4.4A7.5 7.5 0 1 1 13 2z"/>
                    </svg>
                    <span>{{ __('Deals') }}</span>
                    <span class="rounded-md bg-store-hot-wash px-[7px] py-0.5 text-[9.5px] font-bold tracking-[0.1em] text-store-hot-ink">
                        {{ __('HOT') }}
                    </span>
                </a>
            </nav>

            {{-- Tools --}}
            <div class="ml-auto flex min-w-0 shrink items-center gap-2.5">
                {{-- Quick search --}}
                <form
                    action="{{ route('store.products') }}"
                    method="GET"
                    class="hidden min-w-0 shrink basis-[260px] max-[1240px]:basis-[200px] min-[1000px]:block"
                >
                    <label class="flex h-10 min-w-0 items-center gap-[9px] rounded-full border border-[rgba(20,24,29,0.08)] bg-store-chalk pr-2 pl-3.5 transition-colors duration-200 focus-within:border-store-flame focus-within:bg-white">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8B949E" stroke-width="2.2" stroke-linecap="round" class="shrink-0">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="M20 20l-3.5-3.5"/>
                        </svg>
                        <input
                            id="navbar-quick-search"
                            type="search"
                            name="q"
                            placeholder="{{ __('Search cameras, lenses, drones') }}"
                            class="min-w-0 flex-1 border-0 bg-transparent text-[13.5px] text-store-ink placeholder:text-store-faint focus:outline-none [&::-webkit-search-cancel-button]:hidden"
                        />
                        <kbd class="pointer-events-none shrink-0 rounded-md border border-[rgba(20,24,29,0.10)] bg-white px-[7px] py-[3px] font-sans text-[10.5px] font-semibold text-store-slate">
                            &#8984;K
                        </kbd>
                    </label>
                </form>

                {{-- WhatsApp support --}}
                <a
                    href="https://wa.me/94770000000"
                    target="_blank"
                    rel="noopener noreferrer"
                    title="{{ __('WhatsApp Direct Support') }}"
                    class="hidden h-10 shrink-0 items-center gap-2 rounded-full border border-[rgba(20,24,29,0.10)] bg-white px-4 text-[13.5px] font-semibold text-store-ink no-underline transition-colors duration-200 hover:border-store-ink min-[1120px]:inline-flex"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#E4572E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 1.9.6 2.8a2 2 0 0 1-.5 2.1L8.1 9.7a16 16 0 0 0 6 6l1.1-1.1a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.9 2.2z"/>
                    </svg>
                    <span>{{ __('Support') }}</span>
                </a>

                {{-- Auth --}}
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        wire:navigate
                        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full border border-[rgba(20,24,29,0.10)] bg-white px-4 text-[13.5px] font-semibold text-store-ink no-underline transition-[background-color,border-color,color,transform] duration-[260ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.03] hover:border-store-ink hover:bg-store-ink hover:text-white"
                    >
                        <span class="flex size-[18px] items-center justify-center rounded-full bg-store-flame text-[9.5px] font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                        <span class="hidden sm:inline">{{ __('Dashboard') }}</span>
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full border border-[rgba(20,24,29,0.10)] bg-white px-3 text-[13.5px] font-semibold text-store-ink no-underline sm:px-[17px] transition-[background-color,border-color,color,transform] duration-[260ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.03] hover:border-store-ink hover:bg-store-ink hover:text-white"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="12" cy="8" r="3.6"/>
                            <path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>
                        </svg>
                        <span class="hidden sm:inline">{{ __('Sign in') }}</span>
                    </a>
                @endauth

                {{-- Drawer trigger. The design has no small-screen treatment, so the
                     pill keeps a constant height and folds into this below 1000px. --}}
                <button
                    type="button"
                    @click="mobileOpen = ! mobileOpen"
                    aria-label="{{ __('Toggle Menu') }}"
                    :aria-expanded="mobileOpen.toString()"
                    class="flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-full border border-[rgba(20,24,29,0.10)] bg-white text-store-ink transition-colors duration-200 hover:border-store-ink min-[1000px]:hidden"
                >
                    <span class="relative size-4">
                        <span class="absolute left-0 top-[3px] h-0.5 w-4 rounded-full bg-store-ink transition-transform duration-300" :class="mobileOpen ? 'translate-y-[5px] rotate-45' : ''"></span>
                        <span class="absolute left-0 top-2 h-0.5 w-4 rounded-full bg-store-ink transition-opacity duration-200" :class="mobileOpen ? 'opacity-0' : 'opacity-100'"></span>
                        <span class="absolute left-0 top-[13px] h-0.5 w-4 rounded-full bg-store-ink transition-transform duration-300" :class="mobileOpen ? '-translate-y-[5px] -rotate-45' : ''"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- Drawer --}}
        <div
            x-show="mobileOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-[250ms]"
            x-transition:enter-start="opacity-0 -translate-y-3 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-3 scale-[0.98]"
            class="mt-2.5 max-h-[calc(100svh-110px)] overflow-y-auto rounded-3xl border border-[rgba(20,24,29,0.07)] bg-white p-4 shadow-[0_30px_70px_-20px_rgba(0,0,0,0.45)] min-[1000px]:hidden"
        >
            <form action="{{ route('store.products') }}" method="GET" class="mb-3">
                <label class="flex h-11 items-center gap-2.5 rounded-2xl border border-[rgba(20,24,29,0.08)] bg-store-chalk px-3.5 focus-within:border-store-flame focus-within:bg-white">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8B949E" stroke-width="2.2" stroke-linecap="round" class="shrink-0">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M20 20l-3.5-3.5"/>
                    </svg>
                    <input
                        type="search"
                        name="q"
                        placeholder="{{ __('Search cameras, lenses, drones') }}"
                        class="min-w-0 flex-1 border-0 bg-transparent text-sm text-store-ink placeholder:text-store-faint focus:outline-none"
                    />
                </label>
            </form>

            <div class="space-y-1 border-b border-[rgba(20,24,29,0.07)] pb-3">
                @foreach ($links as $link)
                    <a
                        href="{{ $link['href'] }}"
                        wire:navigate
                        @click="mobileOpen = false"
                        @class([
                            'flex items-center justify-between rounded-2xl px-3 py-2.5 text-sm font-semibold no-underline',
                            'bg-store-ink text-white' => $link['active'],
                            'text-store-ink hover:bg-store-chalk' => ! $link['active'],
                        ])
                    >
                        <span>{{ $link['label'] }}</span>
                        <svg class="size-4 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>
                @endforeach

                <a
                    href="{{ route('store.products') }}"
                    wire:navigate
                    @click="mobileOpen = false"
                    @class([
                        'flex items-center justify-between rounded-2xl px-3 py-2.5 text-sm font-semibold no-underline',
                        'bg-store-ink text-white' => $gearActive,
                        'text-store-ink hover:bg-store-chalk' => ! $gearActive,
                    ])
                >
                    <span>{{ __('All Gear') }}</span>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                        'bg-white/15 text-white' => $gearActive,
                        'bg-store-chalk text-store-slate' => ! $gearActive,
                    ])>
                        {{ $productCount }}
                    </span>
                </a>

                <a
                    href="{{ route('store.products') }}"
                    wire:navigate
                    @click="mobileOpen = false"
                    class="flex items-center justify-between rounded-2xl px-3 py-2.5 text-sm font-semibold text-store-ink no-underline hover:bg-store-chalk"
                >
                    <span class="inline-flex items-center gap-2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#E4572E">
                            <path d="M13 2s1 3.2-1.6 6C9 10.5 7 12.2 7 15a5 5 0 0 0 10 0c0-2-1-3.4-1-3.4s2.5 1.2 2.5 4.4A7.5 7.5 0 1 1 13 2z"/>
                        </svg>
                        <span>{{ __('Deals') }}</span>
                    </span>
                    <span class="rounded-md bg-store-hot-wash px-[7px] py-0.5 text-[9.5px] font-bold tracking-[0.1em] text-store-hot-ink">
                        {{ __('HOT') }}
                    </span>
                </a>
            </div>

            <div class="py-3">
                <div class="mb-2 px-3 text-[10.5px] font-bold uppercase tracking-[0.14em] text-store-faint">
                    {{ __('Popular categories') }}
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @forelse ($categories as $cat)
                        <a
                            href="{{ route('store.products', ['category' => $cat->slug]) }}"
                            wire:navigate
                            @click="mobileOpen = false"
                            class="flex items-center gap-2 rounded-2xl bg-store-chalk p-2.5 text-xs font-semibold text-store-ink no-underline transition-colors hover:bg-store-flame hover:text-white"
                        >
                            <span class="size-1.5 shrink-0 rounded-full bg-store-flame"></span>
                            <span class="truncate">{{ $cat->name }}</span>
                        </a>
                    @empty
                        <a
                            href="{{ route('store.products') }}"
                            wire:navigate
                            @click="mobileOpen = false"
                            class="col-span-2 py-2 text-center text-xs text-store-slate no-underline"
                        >
                            {{ __('All gear available') }}
                        </a>
                    @endforelse
                </div>
            </div>

            @if ($featured->isNotEmpty())
                <div class="border-t border-[rgba(20,24,29,0.07)] py-3">
                    <div class="mb-2 flex items-center justify-between px-3">
                        <span class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-store-faint">
                            {{ __('New in stock') }}
                        </span>
                        <a
                            href="{{ route('store.products') }}"
                            wire:navigate
                            @click="mobileOpen = false"
                            class="text-[11px] font-semibold text-store-flame no-underline"
                        >
                            {{ __('View all') }} &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($featured->take(4) as $item)
                            <a
                                href="{{ $item['href'] }}"
                                wire:navigate
                                @click="mobileOpen = false"
                                class="flex items-center gap-2.5 rounded-2xl bg-store-chalk p-2 no-underline transition-colors hover:bg-store-wash"
                            >
                                <span class="relative block size-11 shrink-0 overflow-hidden rounded-xl bg-store-wash">
                                    @if ($item['image'])
                                        <img
                                            src="{{ $item['image'] }}"
                                            alt="{{ $item['alt'] }}"
                                            loading="lazy"
                                            class="absolute inset-0 size-full object-cover"
                                        />
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[11.5px] font-semibold text-store-ink">
                                        {{ $item['name'] }}
                                    </span>
                                    <span class="block text-[11px] font-bold text-store-flame">
                                        {{ $item['price'] }}
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-2 flex items-center justify-between gap-3 rounded-2xl bg-store-chalk p-3 text-xs text-store-slate">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="size-4 text-store-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span class="font-medium">{{ __('2-Yr local warranty') }}</span>
                </span>
                <a
                    href="https://wa.me/94770000000"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="shrink-0 font-bold text-store-flame no-underline hover:underline"
                >
                    {{ __('WhatsApp') }} &rarr;
                </a>
            </div>
        </div>
    </div>
</header>
