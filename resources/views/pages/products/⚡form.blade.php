<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?Product $product = null;

    public string $brandId = '';

    public string $categoryId = '';

    public string $subCategoryId = '';

    public string $name = '';

    public string $basePrice = '';

    public string $discountPrice = '';

    public string $description = '';

    /**
     * The editable list of key feature lines.
     *
     * @var array<int, string>
     */
    public array $keyFeatures = [''];

    /**
     * Freshly uploaded images that have not been persisted yet.
     *
     * @var array<int, mixed>
     */
    public array $newImages = [];

    /**
     * Ids of already stored images staged for deletion.
     *
     * @var array<int, int>
     */
    public array $removeImageIds = [];

    /**
     * Populate the form, either blank or from the product being edited.
     */
    public function mount(?Product $product = null): void
    {
        if (! $product?->exists) {
            return;
        }

        $product->load('subCategory', 'images');

        $this->product = $product;
        $this->brandId = $product->brand_id === null ? '' : (string) $product->brand_id;
        $this->categoryId = (string) $product->subCategory->category_id;
        $this->subCategoryId = (string) $product->sub_category_id;
        $this->name = $product->name;
        $this->basePrice = (string) $product->base_price;
        $this->discountPrice = $product->discount_price === null ? '' : (string) $product->discount_price;
        $this->description = (string) $product->description;
        $this->keyFeatures = $product->key_features ?: [''];
    }

    /**
     * Get every brand for the brand select.
     *
     * @return Collection<int, Brand>
     */
    #[Computed]
    public function brands(): Collection
    {
        return Brand::query()->orderBy('name')->get();
    }

    /**
     * Get every main category for the category select.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * Get the sub categories available for the chosen main category.
     *
     * @return Collection<int, SubCategory>
     */
    #[Computed]
    public function subCategories(): Collection
    {
        if ($this->categoryId === '') {
            return new Collection;
        }

        return SubCategory::query()
            ->where('category_id', $this->categoryId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the stored images that are not staged for deletion.
     *
     * @return Collection<int, ProductImage>
     */
    #[Computed]
    public function existingImages(): Collection
    {
        if (! $this->product) {
            return new Collection;
        }

        return $this->product->images->reject(fn (ProductImage $image) => in_array($image->id, $this->removeImageIds, true))->values();
    }

    /**
     * Clear the sub category when the main category changes.
     */
    public function updatedCategoryId(): void
    {
        $this->subCategoryId = '';
    }

    /**
     * Append an empty key feature line.
     */
    public function addFeature(): void
    {
        $this->keyFeatures[] = '';
    }

    /**
     * Remove a key feature line, always leaving at least one input.
     */
    public function removeFeature(int $index): void
    {
        unset($this->keyFeatures[$index]);

        $this->keyFeatures = array_values($this->keyFeatures);

        if ($this->keyFeatures === []) {
            $this->keyFeatures = [''];
        }
    }

    /**
     * Drop a queued upload before it is persisted.
     */
    public function removeNewImage(int $index): void
    {
        unset($this->newImages[$index]);

        $this->newImages = array_values($this->newImages);
    }

    /**
     * Stage a stored image for deletion, applied when the form is saved.
     */
    public function removeExistingImage(int $id): void
    {
        $this->removeImageIds[] = $id;

        unset($this->existingImages);
    }

    /**
     * Undo every staged image deletion.
     */
    public function restoreImages(): void
    {
        $this->removeImageIds = [];

        unset($this->existingImages);
    }

    /**
     * Persist the product, its images and its key features.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'brandId' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'categoryId' => ['required', 'integer', Rule::exists('categories', 'id')],
            'subCategoryId' => [
                'required',
                'integer',
                Rule::exists('sub_categories', 'id')->where('category_id', $this->categoryId),
            ],
            'basePrice' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'discountPrice' => ['nullable', 'numeric', 'min:0', 'lt:basePrice'],
            'description' => ['nullable', 'string', 'max:65535'],
            'keyFeatures' => ['array', 'max:50'],
            'keyFeatures.*' => ['nullable', 'string', 'max:255'],
            'newImages' => ['array', 'max:10'],
            'newImages.*' => ['image', 'max:2048'],
        ]);

        $features = collect($validated['keyFeatures'] ?? [])
            ->map(fn (?string $feature) => trim((string) $feature))
            ->filter()
            ->values()
            ->all();

        $product = DB::transaction(function () use ($validated, $features): Product {
            $product = $this->product ?? new Product;

            $product->fill([
                'brand_id' => $validated['brandId'] === '' ? null : $validated['brandId'],
                'sub_category_id' => (int) $validated['subCategoryId'],
                'name' => $validated['name'],
                'base_price' => $validated['basePrice'],
                'discount_price' => $validated['discountPrice'] === '' ? null : $validated['discountPrice'],
                'description' => HtmlSanitizer::clean($validated['description'] ?? null),
                'key_features' => $features === [] ? null : $features,
            ]);

            $product->save();

            if ($this->removeImageIds !== []) {
                $removed = ProductImage::query()
                    ->where('product_id', $product->id)
                    ->whereIn('id', $this->removeImageIds)
                    ->get();

                Storage::disk('public')->delete($removed->pluck('path')->all());

                ProductImage::query()->whereKey($removed->modelKeys())->delete();
            }

            $sortOrder = (int) ProductImage::query()->where('product_id', $product->id)->max('sort_order');

            foreach ($this->newImages as $upload) {
                $product->images()->create([
                    'path' => $upload->store('products', 'public'),
                    'sort_order' => ++$sortOrder,
                ]);
            }

            return $product;
        });

        session()->flash('status', $this->product
            ? __('Product updated.')
            : __('Product created.'));

        $this->redirectRoute('products.index', navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:button
            variant="ghost"
            size="sm"
            icon="arrow-left"
            :href="route('products.index')"
            wire:navigate
            class="-ms-2 mb-3"
        >
            {{ __('Back to products') }}
        </flux:button>

        <flux:heading size="xl" level="1">
            {{ $product ? __('Edit product') : __('New product') }}
        </flux:heading>
        <flux:subheading size="lg">
            {{ __('Images, pricing, category and specifications.') }}
        </flux:subheading>

        <flux:separator variant="subtle" class="mt-6" />
    </div>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="grid gap-6 lg:col-span-2">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

            {{-- items-start: a Flux field is itself a grid, so a stretched
                 cell would spread its label and input apart and knock the
                 two columns out of alignment... --}}
            <div class="grid items-start gap-6 sm:grid-cols-2">
                <flux:input
                    wire:model="basePrice"
                    :label="__('Base price')"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                />

                <flux:input
                    wire:model="discountPrice"
                    :label="__('Discount price')"
                    {{-- Trailing, so the hint sits under the input and both price
                         fields stay aligned across the two columns... --}}
                    :description:trailing="__('Optional. Must be lower than the base price.')"
                    type="number"
                    step="0.01"
                    min="0"
                />
            </div>

            <x-rich-text-editor
                model="description"
                :content="$description"
                :label="__('Description')"
                :description="__('Formatted product copy shown on the product page.')"
            />

            <div class="grid gap-2">
                <flux:label>{{ __('Key features') }}</flux:label>
                <flux:description>
                    {{ __('One specification per line, for example “4K 240fps slow motion”.') }}
                </flux:description>

                <div class="grid gap-2">
                    @foreach ($keyFeatures as $index => $feature)
                        <div class="flex items-start gap-2" wire:key="feature-{{ $index }}">
                            <flux:input
                                wire:model="keyFeatures.{{ $index }}"
                                type="text"
                                :placeholder="__('e.g. 4K 240fps slow motion')"
                                class="flex-1"
                            />

                            <flux:button
                                type="button"
                                variant="subtle"
                                icon="trash"
                                wire:click="removeFeature({{ $index }})"
                                :aria-label="__('Remove feature')"
                            />
                        </div>
                    @endforeach
                </div>

                <div>
                    <flux:button type="button" size="sm" variant="filled" icon="plus" wire:click="addFeature">
                        {{ __('Add feature') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="grid gap-6">
            <flux:select wire:model="brandId" :label="__('Brand')" :placeholder="__('No brand')">
                <flux:select.option value="">{{ __('No brand') }}</flux:select.option>
                @foreach ($this->brands as $brand)
                    <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="categoryId" :label="__('Category')" :placeholder="__('Choose a category')" required>
                @foreach ($this->categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select
                wire:model="subCategoryId"
                :label="__('Sub category')"
                :placeholder="$categoryId === '' ? __('Choose a category first') : __('Choose a sub category')"
                :disabled="$categoryId === ''"
                required
            >
                @foreach ($this->subCategories as $subCategory)
                    <flux:select.option value="{{ $subCategory->id }}">{{ $subCategory->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-2">
                <flux:input
                    wire:model="newImages"
                    :label="__('Images')"
                    :description="__('JPG, PNG or WebP up to 2 MB each. The first image is used in listings.')"
                    type="file"
                    accept="image/*"
                    multiple
                />

                <div wire:loading wire:target="newImages">
                    <flux:text size="sm">{{ __('Uploading...') }}</flux:text>
                </div>

                @if ($this->existingImages->isNotEmpty() || $newImages)
                    <div class="mt-1 grid grid-cols-3 gap-2">
                        @foreach ($this->existingImages as $image)
                            <div class="group relative" wire:key="image-{{ $image->id }}">
                                <img src="{{ $image->imageUrl() }}" alt="" class="aspect-square w-full rounded-lg object-cover" />
                                <button
                                    type="button"
                                    wire:click="removeExistingImage({{ $image->id }})"
                                    class="absolute end-1 top-1 rounded-full bg-white/90 p-1 text-zinc-700 shadow-sm hover:text-red-600 dark:bg-zinc-800/90 dark:text-zinc-200"
                                    aria-label="{{ __('Remove image') }}"
                                >
                                    <flux:icon.x-mark variant="micro" />
                                </button>
                            </div>
                        @endforeach

                        @foreach ($newImages as $index => $upload)
                            <div class="group relative" wire:key="new-image-{{ $index }}">
                                @if ($upload->isPreviewable())
                                    <img src="{{ $upload->temporaryUrl() }}" alt="" class="aspect-square w-full rounded-lg object-cover ring-2 ring-accent" />
                                @else
                                    {{-- Not an image Livewire can preview; validation will reject it on save. --}}
                                    <div class="flex aspect-square w-full items-center justify-center rounded-lg bg-zinc-100 ring-2 ring-red-500 dark:bg-zinc-700">
                                        <flux:icon.document variant="micro" class="text-zinc-400" />
                                    </div>
                                @endif
                                <button
                                    type="button"
                                    wire:click="removeNewImage({{ $index }})"
                                    class="absolute end-1 top-1 rounded-full bg-white/90 p-1 text-zinc-700 shadow-sm hover:text-red-600 dark:bg-zinc-800/90 dark:text-zinc-200"
                                    aria-label="{{ __('Remove image') }}"
                                >
                                    <flux:icon.x-mark variant="micro" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($removeImageIds)
                    <flux:text size="sm">
                        {{ trans_choice(':count image will be deleted on save.|:count images will be deleted on save.', count($removeImageIds), ['count' => count($removeImageIds)]) }}

                        <flux:link class="cursor-pointer" wire:click.prevent="restoreImages">{{ __('Undo') }}</flux:link>
                    </flux:text>
                @endif

            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="filled" :href="route('products.index')" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="primary" type="submit" data-test="save-product-button">
                    {{ $product ? __('Save changes') : __('Create product') }}
                </flux:button>
            </div>
        </div>
    </form>
</section>
