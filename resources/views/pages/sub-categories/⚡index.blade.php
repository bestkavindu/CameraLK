<?php

use App\Models\Category;
use App\Models\SubCategory;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Sub categories')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $filterCategory = '';

    public ?int $subCategoryId = null;

    public string $categoryId = '';

    public string $name = '';

    public string $slug = '';

    public $image = null;

    public ?string $currentImage = null;

    public ?int $deletingId = null;

    /**
     * Keep the slug in sync with the name until it is edited by hand.
     */
    public bool $autoSlug = true;

    /**
     * Get the paginated sub categories matching the current filters.
     *
     * @return LengthAwarePaginator<int, SubCategory>
     */
    #[Computed]
    public function subCategories(): LengthAwarePaginator
    {
        return SubCategory::query()
            ->with('category')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterCategory !== '', fn ($query) => $query->where('category_id', $this->filterCategory))
            ->latest()
            ->paginate(10);
    }

    /**
     * Get every main category for the form and filter selects.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * Reset pagination whenever the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination whenever the category filter changes.
     */
    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    /**
     * Derive the slug from the name while the slug has not been touched.
     */
    public function updatedName(string $value): void
    {
        if ($this->autoSlug) {
            $this->slug = Str::slug($value);
        }
    }

    /**
     * Stop deriving the slug once it has been edited by hand.
     */
    public function updatedSlug(): void
    {
        $this->autoSlug = false;
    }

    /**
     * Open the form modal for a new sub category.
     */
    public function create(): void
    {
        $this->resetForm();

        Flux::modal('sub-category-form')->show();
    }

    /**
     * Open the form modal for an existing sub category.
     */
    public function edit(int $id): void
    {
        $subCategory = SubCategory::findOrFail($id);

        $this->resetValidation();

        $this->subCategoryId = $subCategory->id;
        $this->categoryId = (string) $subCategory->category_id;
        $this->name = $subCategory->name;
        $this->slug = $subCategory->slug;
        $this->currentImage = $subCategory->imageUrl();
        $this->image = null;
        $this->autoSlug = false;

        Flux::modal('sub-category-form')->show();
    }

    /**
     * Store or update the sub category.
     */
    public function save(): void
    {
        $this->slug = Str::slug($this->slug !== '' ? $this->slug : $this->name);

        $validated = $this->validate([
            'categoryId' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sub_categories', 'slug')->ignore($this->subCategoryId),
            ],
            'image' => ['nullable', 'image', 'max:2048'],
        ], attributes: [
            // More specific than the global "category" name, since this form's
            // category select is explicitly the main category...
            'categoryId' => __('main category'),
        ]);

        $isUpdate = $this->subCategoryId !== null;

        $subCategory = $isUpdate
            ? SubCategory::findOrFail($this->subCategoryId)
            : new SubCategory;

        $subCategory->fill([
            'category_id' => (int) $validated['categoryId'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        if ($this->image) {
            $previous = $subCategory->image;

            $subCategory->image = $this->image->store('sub-categories', 'public');

            if ($previous) {
                Storage::disk('public')->delete($previous);
            }
        }

        $subCategory->save();

        unset($this->subCategories);

        Flux::modal('sub-category-form')->close();

        Flux::toast(variant: 'success', text: $isUpdate
            ? __('Sub category updated.')
            : __('Sub category created.'));

        $this->resetForm();
    }

    /**
     * Remove the stored image from the sub category being edited.
     */
    public function removeImage(): void
    {
        if (! $this->subCategoryId) {
            $this->image = null;

            return;
        }

        $subCategory = SubCategory::findOrFail($this->subCategoryId);

        if ($subCategory->image) {
            Storage::disk('public')->delete($subCategory->image);

            $subCategory->update(['image' => null]);
        }

        $this->image = null;
        $this->currentImage = null;

        unset($this->subCategories);
    }

    /**
     * Open the delete confirmation modal.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;

        Flux::modal('sub-category-delete')->show();
    }

    /**
     * Delete the sub category along with its image.
     */
    public function delete(): void
    {
        $subCategory = SubCategory::findOrFail($this->deletingId);

        if ($subCategory->image) {
            Storage::disk('public')->delete($subCategory->image);
        }

        $subCategory->delete();

        $this->deletingId = null;

        unset($this->subCategories);

        Flux::modal('sub-category-delete')->close();

        Flux::toast(variant: 'success', text: __('Sub category deleted.'));
    }

    /**
     * Clear the form state.
     */
    public function resetForm(): void
    {
        $this->reset(['subCategoryId', 'categoryId', 'name', 'slug', 'image', 'currentImage']);

        $this->autoSlug = true;

        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Sub categories') }}</flux:heading>
                <flux:subheading size="lg">{{ __('Manage the sub categories under each main category') }}</flux:subheading>
            </div>

            <flux:button
                variant="primary"
                icon="plus"
                wire:click="create"
                :disabled="$this->categories->isEmpty()"
                data-test="create-sub-category-button"
            >
                {{ __('New sub category') }}
            </flux:button>
        </div>

        <flux:separator variant="subtle" class="mt-6" />
    </div>

    @if ($this->categories->isEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4">
            <flux:callout.heading>{{ __('No main categories yet') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Create a category first — every sub category must belong to one.') }}
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button :href="route('categories.index')" wire:navigate size="sm">
                    {{ __('Go to categories') }}
                </flux:button>
            </x-slot>
        </flux:callout>
    @endif

    <div class="mb-4 flex flex-wrap gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search sub categories...')"
            class="max-w-sm"
            clearable
        />

        <flux:select wire:model.live="filterCategory" :placeholder="__('All categories')" class="max-w-xs">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach ($this->categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->subCategories">
        <flux:table.columns>
            <flux:table.column class="w-20">{{ __('Image') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Slug') }}</flux:table.column>
            <flux:table.column>{{ __('Main category') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->subCategories as $subCategory)
                <flux:table.row :key="$subCategory->id">
                    <flux:table.cell>
                        @if ($subCategory->imageUrl())
                            <img
                                src="{{ $subCategory->imageUrl() }}"
                                alt="{{ $subCategory->name }}"
                                class="size-10 rounded-lg object-cover"
                            />
                        @else
                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon.photo variant="micro" class="text-zinc-400" />
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell variant="strong">{{ $subCategory->name }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">{{ $subCategory->slug }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>{{ $subCategory->category->name }}</flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="pencil-square"
                            wire:click="edit({{ $subCategory->id }})"
                            :aria-label="__('Edit')"
                        />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="confirmDelete({{ $subCategory->id }})"
                            :aria-label="__('Delete')"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" align="center" class="py-10">
                        <flux:text>{{ __('No sub categories found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="sub-category-form" class="w-full max-w-lg" wire:close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $subCategoryId ? __('Edit sub category') : __('New sub category') }}
                </flux:heading>
                <flux:subheading>{{ __('Name, slug, image and the main category it belongs to.') }}</flux:subheading>
            </div>

            <flux:select wire:model="categoryId" :label="__('Main category')" :placeholder="__('Choose a category')" required>
                @foreach ($this->categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live.debounce.400ms="name" :label="__('Name')" type="text" required autofocus />

            <flux:input
                wire:model.live.debounce.400ms="slug"
                :label="__('Slug')"
                :description="__('Used in URLs. Generated from the name unless you change it.')"
                type="text"
                required
            />

            <div>
                <flux:input
                    wire:model="image"
                    :label="__('Image')"
                    type="file"
                    accept="image/*"
                />

                <div wire:loading wire:target="image">
                    <flux:text size="sm" class="mt-2">{{ __('Uploading...') }}</flux:text>
                </div>

                @if ($image && ! $errors->has('image') && $image->isPreviewable())
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $image->temporaryUrl() }}" alt="" class="size-16 rounded-lg object-cover" />
                        <flux:text size="sm">{{ __('New image selected.') }}</flux:text>
                    </div>
                @elseif ($currentImage)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $currentImage }}" alt="" class="size-16 rounded-lg object-cover" />
                        <flux:button size="sm" variant="subtle" type="button" wire:click="removeImage">
                            {{ __('Remove image') }}
                        </flux:button>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-sub-category-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="sub-category-delete" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete sub category?') }}</flux:heading>
                <flux:subheading>{{ __('This cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete" data-test="confirm-delete-sub-category-button">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
