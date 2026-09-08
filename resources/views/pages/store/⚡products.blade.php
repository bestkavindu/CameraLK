<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::store')] #[Title('Products')] class extends Component {
    /**
     * How many products are added to the grid each time.
     */
    public const PER_PAGE = 12;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'brand', except: '')]
    public string $brand = '';

    #[Url(as: 'sort', except: 'featured')]
    public string $sort = 'featured';

    /**
     * How many products the grid currently shows.
     */
    public int $shown = self::PER_PAGE;

    /**
     * Get the products currently rendered in the grid.
     *
     * @return EloquentCollection<int, Product>
     */
    #[Computed]
    public function products(): EloquentCollection
    {
        return $this->ordered($this->filtered())
            ->with(['brand', 'images', 'subCategory.category'])
            ->take($this->shown)
            ->get();
    }

    /**
     * Get the display-ready card data for the grid.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function cards(): Collection
    {
        return $this->products->map(function (Product $product): array {
            $off = $product->discountPercentage();

            return [
                'id' => $product->id,
                'eyebrow' => $product->brand?->name ?? $product->subCategory->name,
                'name' => $product->name,
                'alt' => trim(($product->brand?->name ?? '').' '.$product->name),
                'image' => $product->primaryImage()?->imageUrl(),
                'description' => $this->excerpt($product->description),
                'price' => $this->money($product->effectivePrice()),
                'was' => $product->discount_price === null ? null : $this->money($product->base_price),
                'off' => $off === null || $off <= 0 ? null : '-'.$off.'% '.__('off'),
            ];
        });
    }

    /**
     * Get the number of products matching every active filter.
     */
    #[Computed]
    public function total(): int
    {
        return $this->filtered()->count();
    }

    /**
     * Get how many more products could still be loaded into the grid.
     */
    #[Computed]
    public function remaining(): int
    {
        return max(0, $this->total - $this->products->count());
    }

    /**
     * Get how many products the next click of the load more button adds.
     */
    #[Computed]
    public function nextPageSize(): int
    {
        return min($this->remaining, self::PER_PAGE);
    }

    /**
     * Get the category chips, each counted against the search and brand filters.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function categoryChips(): Collection
    {
        $counts = $this->filtered(category: '')
            ->toBase()
            ->join('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->selectRaw('sub_categories.category_id as category_id, count(*) as total')
            ->groupBy('sub_categories.category_id')
            ->get()
            ->pluck('total', 'category_id');

        $chips = Category::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'slug' => $category->slug,
                'label' => $category->name,
                'count' => (int) $counts->get($category->id, 0),
                'active' => $this->category === $category->slug,
            ]);

        return $chips->prepend([
            'slug' => '',
            'label' => __('All gear'),
            'count' => (int) $counts->sum(),
            'active' => $this->category === '',
        ])->values();
    }

    /**
     * Get the brand filter options keyed by slug.
     *
     * @return Collection<string, string>
     */
    #[Computed]
    public function brandOptions(): Collection
    {
        return Brand::query()
            ->whereHas('products')
            ->orderBy('name')
            ->pluck('name', 'slug');
    }

    /**
     * Get the sort options keyed by their query string value.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function sortOptions(): array
    {
        return [
            'featured' => __('Sort: Featured'),
            'price-asc' => __('Price: low to high'),
            'price-desc' => __('Price: high to low'),
            'discount' => __('Biggest discount'),
            'name' => __('Name: A–Z'),
        ];
    }

    /**
     * Determine whether any filter is narrowing the catalogue.
     */
    #[Computed]
    public function isFiltered(): bool
    {
        return $this->search !== '' || $this->category !== '' || $this->brand !== '';
    }

    /**
     * Show the products of a single main category, or all of them.
     */
    public function selectCategory(string $slug): void
    {
        $this->category = $slug;

        $this->resetGrid();
    }

    /**
     * Empty the search box.
     */
    public function clearSearch(): void
    {
        $this->search = '';

        $this->resetGrid();
    }

    /**
     * Drop every filter and start from the full catalogue again.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->category = '';
        $this->brand = '';

        $this->resetGrid();
    }

    /**
     * Add another page of products to the grid.
     */
    public function loadMore(): void
    {
        $this->shown += self::PER_PAGE;
    }

    /**
     * Collapse the grid back to one page whenever the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetGrid();
    }

    /**
     * Collapse the grid back to one page whenever the brand filter changes.
     */
    public function updatedBrand(): void
    {
        $this->resetGrid();
    }

    /**
     * Collapse the grid back to one page whenever the sort order changes.
     */
    public function updatedSort(): void
    {
        $this->resetGrid();
    }

    /**
     * Build the catalogue query for the active filters.
     *
     * Pass a category slug to count or list against a category other than the
     * selected one, which is how the chip counts are worked out.
     *
     * @return Builder<Product>
     */
    private function filtered(?string $category = null): Builder
    {
        $category ??= $this->category;

        return Product::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn (Builder $query) => $query
                    ->where('products.name', 'like', $term)
                    ->orWhere('products.description', 'like', $term)
                    ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $term))
                    ->orWhereHas('subCategory', fn (Builder $sub) => $sub->where('name', 'like', $term))
                    ->orWhereHas('subCategory.category', fn (Builder $main) => $main->where('name', 'like', $term)));
            })
            ->when($this->brand !== '', fn (Builder $query) => $query
                ->whereHas('brand', fn (Builder $brand) => $brand->where('slug', $this->brand)))
            ->when($category !== '', fn (Builder $query) => $query
                ->whereHas('subCategory.category', fn (Builder $main) => $main->where('slug', $category)));
    }

    /**
     * Apply the selected sort order, always breaking ties on the id so the
     * grid stays stable as more products are loaded in.
     *
     * The discount ratio is multiplied by 1.0 first because SQLite divides two
     * integers as integers, which would flatten every ratio to zero.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function ordered(Builder $query): Builder
    {
        $query = match ($this->sort) {
            'price-asc' => $query->orderByRaw('coalesce(discount_price, base_price) asc'),
            'price-desc' => $query->orderByRaw('coalesce(discount_price, base_price) desc'),
            'discount' => $query->orderByRaw('case when base_price > 0 then (base_price - coalesce(discount_price, base_price)) * 1.0 / base_price else 0 end desc'),
            'name' => $query->orderBy('products.name'),
            default => $query->orderByDesc('products.created_at'),
        };

        return $query->orderByDesc('products.id');
    }

    /**
     * Collapse the grid back to a single page of products.
     */
    private function resetGrid(): void
    {
        $this->shown = self::PER_PAGE;
    }

    /**
     * Flatten a stored rich text description into a short plain text teaser.
     */
    private function excerpt(?string $description): string
    {
        // Space out the tag boundaries first: stripping the markup off
        // "<p>a.</p><p>b</p>" would otherwise read as "a.b". Entities are
        // decoded afterwards so an encoded tag cannot come back as markup.
        $text = strip_tags(str_replace('<', ' <', (string) $description));

        return Str::limit(Str::squish(html_entity_decode($text, ENT_QUOTES | ENT_HTML5)), 120);
    }

    /**
     * Format a price the way the storefront shows it, hiding empty cents.
     */
    private function money(string $amount): string
    {
        $value = (float) $amount;

        return 'Rs '.number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2);
    }
}; ?>

<div class="mx-auto max-w-[1360px] px-5 pb-20 pt-10 sm:px-8">

    <div class="mb-7 flex flex-wrap items-end justify-between gap-6">
        <div>
            <div class="mb-2.5 text-xs uppercase tracking-[0.14em] text-store-faint">
                {{ __('Shop') }} / {{ __('All gear') }}
            </div>

            <h1 class="font-store-display text-[32px] font-bold leading-[1.05] tracking-[-0.02em] sm:text-[40px]">
                {{ __('Cameras, drones & glass') }}
            </h1>
        </div>

        <p class="max-w-[380px] text-sm leading-[1.55] text-store-muted text-pretty">
            {{ __('Every item ships from our Colombo warehouse with a two-year local warranty. Sale prices update daily.') }}
        </p>
    </div>

    <section class="sticky top-[64px] sm:top-[72px] z-20 mb-2 bg-store-surface/90 backdrop-blur-md pb-3.5 pt-3 transition-all">
        <div class="rounded-[14px] border border-store-line bg-white/95 p-3.5 shadow-[0_1px_2px_rgba(20,24,29,0.04)]">

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-[200px] flex-[1_1_260px]">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[15px] text-store-faint">&#8981;</span>

                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        aria-label="{{ __('Search products') }}"
                        placeholder="{{ __('Search cameras, drones, lenses, brands…') }}"
                        class="h-[46px] w-full rounded-[10px] border border-store-line-strong bg-store-field pl-[34px] pr-10 text-[15px] placeholder:text-store-faint focus:border-store-flame focus:bg-white focus:outline-none focus:ring-[3px] focus:ring-store-flame/12 [&::-webkit-search-cancel-button]:hidden"
                    />

                    @if ($search !== '')
                        <button
                            type="button"
                            wire:click="clearSearch"
                            aria-label="{{ __('Clear search') }}"
                            class="absolute right-2 top-1/2 size-[26px] -translate-y-1/2 rounded-full bg-store-wash leading-none text-store-muted hover:bg-store-line-strong hover:text-store-ink"
                        >&times;</button>
                    @endif
                </div>

                <div class="flex flex-none items-center gap-2.5">
                    <div class="relative">
                        <select
                            wire:model.live="brand"
                            aria-label="{{ __('Filter by brand') }}"
                            class="h-[46px] cursor-pointer appearance-none rounded-[10px] border border-store-line-strong bg-store-field pl-3.5 pr-[34px] text-sm font-medium focus:border-store-flame focus:outline-none"
                        >
                            <option value="">{{ __('All brands') }}</option>
                            @foreach ($this->brandOptions as $slug => $name)
                                <option value="{{ $slug }}">{{ $name }}</option>
                            @endforeach
                        </select>

                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-store-faint">&#9660;</span>
                    </div>

                    <div class="relative">
                        <select
                            wire:model.live="sort"
                            aria-label="{{ __('Sort products') }}"
                            class="h-[46px] cursor-pointer appearance-none rounded-[10px] border border-store-line-strong bg-store-field pl-3.5 pr-[34px] text-sm font-medium focus:border-store-flame focus:outline-none"
                        >
                            @foreach ($this->sortOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-store-faint">&#9660;</span>
                    </div>
                </div>
            </div>

            <div id="categories" class="mt-3.5 flex flex-wrap items-center gap-2 border-t border-store-wash pt-3.5">
                @foreach ($this->categoryChips as $chip)
                    <button
                        type="button"
                        wire:key="chip-{{ $chip['slug'] ?: 'all' }}"
                        wire:click="selectCategory('{{ $chip['slug'] }}')"
                        aria-pressed="{{ $chip['active'] ? 'true' : 'false' }}"
                        @class([
                            'inline-flex h-9 cursor-pointer items-center gap-[7px] rounded-[18px] border px-3.5 text-[13.5px] font-semibold transition-colors',
                            'border-store-ink bg-store-ink text-white' => $chip['active'],
                            'border-store-line-strong bg-white text-store-ink-soft hover:border-store-ink hover:text-store-ink' => ! $chip['active'],
                        ])
                    >
                        <span>{{ $chip['label'] }}</span>

                        <span @class([
                            'rounded-lg px-1.5 py-px text-[11px] font-semibold',
                            'bg-white/20 text-white' => $chip['active'],
                            'bg-store-wash text-store-faint' => ! $chip['active'],
                        ])>{{ $chip['count'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-4 px-0.5 pb-[22px] pt-3.5">
        <p class="text-sm text-store-muted">
            <strong class="font-semibold text-store-ink">{{ $this->total }}</strong>
            {{ trans_choice('product|products', $this->total) }}
            {{ $this->isFiltered ? __('match your filters') : __('in stock') }}
        </p>

        @if ($this->isFiltered)
            <button
                type="button"
                wire:click="resetFilters"
                class="inline-flex h-[34px] cursor-pointer items-center gap-1.5 rounded-[17px] border border-store-line-strong bg-white px-3.5 text-[13px] font-medium text-store-ink-soft transition-colors hover:border-store-ink hover:text-store-ink"
            >
                {{ __('Clear all filters') }}
            </button>
        @endif
    </div>

    @if ($this->cards->isNotEmpty())
        <div class="grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(258px,1fr))]">
            @foreach ($this->cards as $card)
                <article
                    wire:key="product-{{ $card['id'] }}"
                    class="flex flex-col overflow-hidden rounded-[14px] border border-store-line bg-white transition duration-150 ease-out hover:-translate-y-0.5 hover:border-store-line-strong hover:shadow-[0_12px_28px_rgba(20,24,29,0.10)]"
                >
                    <div class="relative aspect-[4/3] bg-store-wash">
                        @if ($card['image'])
                            <img
                                src="{{ $card['image'] }}"
                                alt="{{ $card['alt'] }}"
                                loading="lazy"
                                class="absolute inset-0 size-full object-cover"
                            />
                        @else
                            <div class="absolute inset-0 flex items-center justify-center p-4 text-center text-[13px] font-medium text-store-faint">
                                {{ $card['alt'] }}
                            </div>
                        @endif

                        @if ($card['off'])
                            <span class="pointer-events-none absolute left-2.5 top-2.5 rounded-md bg-store-flame px-[9px] py-1 text-xs font-bold tracking-[0.02em] text-white">
                                {{ $card['off'] }}
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col gap-2 px-4 pb-[18px] pt-4">
                        <div class="text-[11px] uppercase tracking-[0.12em] text-store-faint">{{ $card['eyebrow'] }}</div>

                        <h3 class="text-base font-semibold leading-[1.3] tracking-[-0.01em]">{{ $card['name'] }}</h3>

                        <p class="line-clamp-2 min-h-[39px] text-[13px] leading-[1.5] text-store-muted text-pretty">
                            {{ $card['description'] }}
                        </p>

                        <div class="mt-auto flex items-baseline gap-[9px] pt-3">
                            <span class="text-[19px] font-bold tracking-[-0.01em]">{{ $card['price'] }}</span>

                            @if ($card['was'])
                                <span class="text-[13px] text-store-faint line-through">{{ $card['was'] }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center gap-3 rounded-[14px] border border-dashed border-store-line-strong bg-white px-6 py-[90px] text-center">
            <div class="font-store-display text-[22px] font-bold">{{ __('No gear matched that') }}</div>

            <p class="max-w-[340px] text-sm leading-[1.55] text-store-muted">
                {{ __('Try a shorter search term, or reset the category and brand filters.') }}
            </p>

            <button
                type="button"
                wire:click="resetFilters"
                class="mt-1.5 h-[42px] cursor-pointer rounded-[10px] bg-store-ink px-5 text-sm font-semibold text-white transition-colors hover:bg-store-flame"
            >
                {{ __('Reset filters') }}
            </button>
        </div>
    @endif

    @if ($this->remaining > 0)
        <div class="flex justify-center pt-9">
            <button
                type="button"
                wire:click="loadMore"
                class="h-12 cursor-pointer rounded-3xl border border-store-ink px-[26px] text-sm font-semibold transition-colors hover:bg-store-ink hover:text-white"
            >
                {{ __('Show :count more', ['count' => $this->nextPageSize]) }}
            </button>
        </div>
    @endif

</div>
