<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'brand', except: '')]
    public string $filterBrand = '';

    #[Url(as: 'category', except: '')]
    public string $filterCategory = '';

    #[Url(as: 'sub', except: '')]
    public string $filterSubCategory = '';

    public ?int $deletingId = null;

    /**
     * Surface the flash message left behind by the product form.
     */
    public function mount(): void
    {
        if (is_string($message = session('status'))) {
            Flux::toast(variant: 'success', text: $message);
        }
    }

    /**
     * Get the paginated products matching the current filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['brand', 'images', 'subCategory.category'])
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterBrand !== '', fn ($query) => $query->where('brand_id', $this->filterBrand))
            ->when($this->filterSubCategory !== '', fn ($query) => $query->where('sub_category_id', $this->filterSubCategory))
            ->when(
                $this->filterCategory !== '' && $this->filterSubCategory === '',
                fn ($query) => $query->whereHas('subCategory', fn ($sub) => $sub->where('category_id', $this->filterCategory)),
            )
            ->latest()
            ->paginate(10);
    }

    /**
     * Get every brand for the filter select.
     *
     * @return Collection<int, Brand>
     */
    #[Computed]
    public function brands(): Collection
    {
        return Brand::query()->orderBy('name')->get();
    }

    /**
     * Get every main category for the filter select.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * Get the sub categories available for the selected main category.
     *
     * @return Collection<int, SubCategory>
     */
    #[Computed]
    public function subCategories(): Collection
    {
        return SubCategory::query()
            ->when($this->filterCategory !== '', fn ($query) => $query->where('category_id', $this->filterCategory))
            ->orderBy('name')
            ->get();
    }

    /**
     * Reset pagination whenever the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination whenever the brand filter changes.
     */
    public function updatedFilterBrand(): void
    {
        $this->resetPage();
    }

    /**
     * Narrow the sub category filter to the selected main category.
     */
    public function updatedFilterCategory(): void
    {
        $this->filterSubCategory = '';

        $this->resetPage();
    }

    /**
     * Reset pagination whenever the sub category filter changes.
     */
    public function updatedFilterSubCategory(): void
    {
        $this->resetPage();
    }

    /**
     * Open the delete confirmation modal.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;

        Flux::modal('product-delete')->show();
    }

    /**
     * Delete the product along with every stored image.
     */
    public function delete(): void
    {
        $product = Product::with('images')->findOrFail($this->deletingId);

        Storage::disk('public')->delete($product->images->pluck('path')->all());

        $product->delete();

        $this->deletingId = null;

        unset($this->products);

        Flux::modal('product-delete')->close();

        Flux::toast(variant: 'success', text: __('Product deleted.'));
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Products') }}</flux:heading>
                <flux:subheading size="lg">{{ __('Manage your product catalogue') }}</flux:subheading>
            </div>

            <flux:button
                variant="primary"
                icon="plus"
                :href="route('products.create')"
                wire:navigate
                data-test="create-product-button"
            >
                {{ __('New product') }}
            </flux:button>
        </div>

        <flux:separator variant="subtle" class="mt-6" />
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search products...')"
            class="max-w-sm"
            clearable
        />

        <flux:select wire:model.live="filterBrand" class="max-w-xs">
            <flux:select.option value="">{{ __('All brands') }}</flux:select.option>
            @foreach ($this->brands as $brand)
                <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterCategory" class="max-w-xs">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach ($this->categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterSubCategory" class="max-w-xs">
            <flux:select.option value="">{{ __('All sub categories') }}</flux:select.option>
            @foreach ($this->subCategories as $subCategory)
                <flux:select.option value="{{ $subCategory->id }}">{{ $subCategory->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->products">
        <flux:table.columns>
            <flux:table.column class="w-20">{{ __('Image') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Brand') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Price') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Features') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell>
                        @if ($image = $product->primaryImage())
                            <img
                                src="{{ $image->imageUrl() }}"
                                alt="{{ $product->name }}"
                                class="size-10 rounded-lg object-cover"
                            />
                        @else
                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon.photo variant="micro" class="text-zinc-400" />
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell variant="strong">
                        {{ $product->name }}

                        @if ($count = $product->images->count() - 1)
                            <flux:text size="sm" class="mt-0.5">
                                {{ trans_choice('+:count more image|+:count more images', $count, ['count' => $count]) }}
                            </flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($product->brand)
                            {{ $product->brand->name }}
                        @else
                            <flux:text size="sm">{{ __('—') }}</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $product->subCategory->name }}
                        <flux:text size="sm" class="mt-0.5">{{ $product->subCategory->category->name }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        @if ($product->discount_price !== null)
                            <div class="flex items-center justify-end gap-2">
                                <flux:text size="sm" class="line-through">{{ number_format((float) $product->base_price, 2) }}</flux:text>
                                <span class="font-medium text-zinc-800 dark:text-white">{{ number_format((float) $product->discount_price, 2) }}</span>
                                <flux:badge size="sm" color="green">-{{ $product->discountPercentage() }}%</flux:badge>
                            </div>
                        @else
                            <span class="font-medium text-zinc-800 dark:text-white">{{ number_format((float) $product->base_price, 2) }}</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="center">{{ count($product->key_features ?? []) }}</flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="pencil-square"
                            :href="route('products.edit', $product)"
                            wire:navigate
                            :aria-label="__('Edit')"
                        />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="confirmDelete({{ $product->id }})"
                            :aria-label="__('Delete')"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" align="center" class="py-10">
                        <flux:text>{{ __('No products found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="product-delete" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete product?') }}</flux:heading>
                <flux:subheading>{{ __('The product and all of its images will be removed. This cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete" data-test="confirm-delete-product-button">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
