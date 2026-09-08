<?php

use App\Models\Category;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Categories')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $categoryId = null;

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
     * Get the paginated categories matching the current search.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    #[Computed]
    public function categories(): LengthAwarePaginator
    {
        return Category::query()
            ->withCount('subCategories')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);
    }

    /**
     * Reset pagination whenever the search term changes.
     */
    public function updatedSearch(): void
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
     * Open the form modal for a new category.
     */
    public function create(): void
    {
        $this->resetForm();

        Flux::modal('category-form')->show();
    }

    /**
     * Open the form modal for an existing category.
     */
    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->resetValidation();

        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->currentImage = $category->imageUrl();
        $this->image = null;
        $this->autoSlug = false;

        Flux::modal('category-form')->show();
    }

    /**
     * Store or update the category.
     */
    public function save(): void
    {
        $this->slug = Str::slug($this->slug !== '' ? $this->slug : $this->name);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($this->categoryId),
            ],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $isUpdate = $this->categoryId !== null;

        $category = $isUpdate
            ? Category::findOrFail($this->categoryId)
            : new Category;

        $category->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        if ($this->image) {
            $previous = $category->image;

            $category->image = $this->image->store('categories', 'public');

            if ($previous) {
                Storage::disk('public')->delete($previous);
            }
        }

        $category->save();

        unset($this->categories);

        Flux::modal('category-form')->close();

        Flux::toast(variant: 'success', text: $isUpdate
            ? __('Category updated.')
            : __('Category created.'));

        $this->resetForm();
    }

    /**
     * Remove the stored image from the category being edited.
     */
    public function removeImage(): void
    {
        if (! $this->categoryId) {
            $this->image = null;

            return;
        }

        $category = Category::findOrFail($this->categoryId);

        if ($category->image) {
            Storage::disk('public')->delete($category->image);

            $category->update(['image' => null]);
        }

        $this->image = null;
        $this->currentImage = null;

        unset($this->categories);
    }

    /**
     * Open the delete confirmation modal.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;

        Flux::modal('category-delete')->show();
    }

    /**
     * Delete the category along with its image.
     */
    public function delete(): void
    {
        $category = Category::findOrFail($this->deletingId);

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        $this->deletingId = null;

        unset($this->categories);

        Flux::modal('category-delete')->close();

        Flux::toast(variant: 'success', text: __('Category deleted.'));
    }

    /**
     * Clear the form state.
     */
    public function resetForm(): void
    {
        $this->reset(['categoryId', 'name', 'slug', 'image', 'currentImage']);

        $this->autoSlug = true;

        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Categories') }}</flux:heading>
                <flux:subheading size="lg">{{ __('Manage the main categories of your catalogue') }}</flux:subheading>
            </div>

            <flux:button variant="primary" icon="plus" wire:click="create" data-test="create-category-button">
                {{ __('New category') }}
            </flux:button>
        </div>

        <flux:separator variant="subtle" class="mt-6" />
    </div>

    <div class="mb-4 max-w-sm">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search categories...')"
            clearable
        />
    </div>

    <flux:table :paginate="$this->categories">
        <flux:table.columns>
            <flux:table.column class="w-20">{{ __('Image') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Slug') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Sub categories') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->categories as $category)
                <flux:table.row :key="$category->id">
                    <flux:table.cell>
                        @if ($category->imageUrl())
                            <img
                                src="{{ $category->imageUrl() }}"
                                alt="{{ $category->name }}"
                                class="size-10 rounded-lg object-cover"
                            />
                        @else
                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon.photo variant="micro" class="text-zinc-400" />
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell variant="strong">{{ $category->name }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">{{ $category->slug }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell align="center">{{ $category->sub_categories_count }}</flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="pencil-square"
                            wire:click="edit({{ $category->id }})"
                            :aria-label="__('Edit')"
                        />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="confirmDelete({{ $category->id }})"
                            :aria-label="__('Delete')"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" align="center" class="py-10">
                        <flux:text>{{ __('No categories found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="category-form" class="w-full max-w-lg" wire:close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $categoryId ? __('Edit category') : __('New category') }}
                </flux:heading>
                <flux:subheading>{{ __('Name, slug and image for this category.') }}</flux:subheading>
            </div>

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

                <flux:button variant="primary" type="submit" data-test="save-category-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="category-delete" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete category?') }}</flux:heading>
                <flux:subheading>
                    {{ __('This also deletes every sub category that belongs to it. This cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete" data-test="confirm-delete-category-button">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
