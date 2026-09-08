<footer class="mt-auto border-t border-store-line bg-white">
    <div class="mx-auto flex max-w-[1360px] flex-col gap-8 px-5 py-12 sm:px-8">
        <div class="flex flex-wrap justify-between gap-10">
            <div class="max-w-xs">
                <div class="flex items-baseline gap-2.5">
                    <span class="font-store-display text-[19px] font-bold tracking-[-0.01em] text-store-ink">
                        SHUTTER<span class="text-store-flame">&amp;</span>SKY
                    </span>
                    <span class="text-[11px] uppercase tracking-[0.14em] text-store-faint">{{ __('Colombo') }}</span>
                </div>

                <p class="mt-3 text-sm leading-relaxed text-store-muted text-pretty">
                    {{ __('Cameras, drones and glass shipped from our Colombo warehouse with a two-year local warranty.') }}
                </p>
            </div>

            <div>
                <div class="text-[11px] uppercase tracking-[0.14em] text-store-faint">{{ __('Shop') }}</div>

                <ul class="mt-3 space-y-2 text-sm">
                    <li>
                        <a href="{{ route('store.products') }}" wire:navigate class="text-store-ink-soft no-underline hover:text-store-flame hover:no-underline">
                            {{ __('All gear') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('store.products') }}#categories" wire:navigate class="text-store-ink-soft no-underline hover:text-store-flame hover:no-underline">
                            {{ __('Browse categories') }}
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <div class="text-[11px] uppercase tracking-[0.14em] text-store-faint">{{ __('Support') }}</div>

                <ul class="mt-3 space-y-2 text-sm text-store-ink-soft">
                    <li>{{ __('Two-year local warranty') }}</li>
                    <li>{{ __('Island-wide delivery') }}</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-store-wash pt-6 text-[13px] text-store-faint">
            <span>&copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}. {{ __('All rights reserved.') }}</span>
            <span>{{ __('Sale prices update daily.') }}</span>
        </div>
    </div>
</footer>
