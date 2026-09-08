<?php

use App\Models\Brand;
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

new #[Title('Brands')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $brandId = null;

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
     * Get the paginated brands matching the current search.
     *
     * @return LengthAwarePaginator<int, Brand>
     */
    #[Computed]
    public function brands(): LengthAwarePaginator
    {
        return Brand::query()
            ->withCount('products')
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
     * Open the form modal for a new brand.
     */
    public function create(): void
    {
        $this->resetForm();

        Flux::modal('brand-form')->show();
    }

    /**
     * Open the form modal for an existing brand.
     */
    public function edit(int $id): void
    {
        $brand = Brand::findOrFail($id);

        $this->resetValidation();

        $this->brandId = $brand->id;
        $this->name = $brand->name;
        $this->slug = $brand->slug;
        $this->currentImage = $brand->imageUrl();
        $this->image = null;
        $this->autoSlug = false;

        Flux::modal('brand-form')->show();
    }

    /**
     * Store or update the brand.
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
                Rule::unique('brands', 'slug')->ignore($this->brandId),
            ],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $isUpdate = $this->brandId !== null;

        $brand = $isUpdate
            ? Brand::findOrFail($this->brandId)
            : new Brand;

        $brand->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        if ($this->image) {
            $previous = $brand->image;

            $brand->image = $this->image->store('brands', 'public');

            if ($previous) {
                Storage::disk('public')->delete($previous);
            }
        }

        $brand->save();

        unset($this->brands);

        Flux::modal('brand-form')->close();

        Flux::toast(variant: 'success', text: $isUpdate
            ? __('Brand updated.')
            : __('Brand created.'));

        $this->resetForm();
    }

    /**
     * Remove the stored logo from the brand being edited.
     */
    public function removeImage(): void
    {
        if (! $this->brandId) {
            $this->image = null;

            return;
        }

        $brand = Brand::findOrFail($this->brandId);

        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);

            $brand->update(['image' => null]);
        }

        $this->image = null;
        $this->currentImage = null;

        unset($this->brands);
    }

    /**
     * Open the delete confirmation modal.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;

        Flux::modal('brand-delete')->show();
    }

    /**
     * Delete the brand along with its logo.
     */
    public function delete(): void
    {
        $brand = Brand::findOrFail($this->deletingId);

        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }

        $brand->delete();

        $this->deletingId = null;

        unset($this->brands);

        Flux::modal('brand-delete')->close();

        Flux::toast(variant: 'success', text: __('Brand deleted.'));
    }

    /**
     * Clear the form state.
     */
    public function resetForm(): void
    {
        $this->reset(['brandId', 'name', 'slug', 'image', 'currentImage']);

        $this->autoSlug = true;

        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Brands') }}</flux:heading>
                <flux:subheading size="lg">{{ __('Manage the brands your products are sold under') }}</flux:subheading>
            </div>

            <flux:button variant="primary" icon="plus" wire:click="create" data-test="create-brand-button">
                {{ __('New brand') }}
            </flux:button>
        </div>

        <flux:separator variant="subtle" class="mt-6" />
    </div>

    <div class="mb-4 max-w-sm">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search brands...')"
            clearable
        />
    </div>

    <flux:table :paginate="$this->brands">
        <flux:table.columns>
            <flux:table.column class="w-20">{{ __('Logo') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Slug') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Products') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->brands as $brand)
                <flux:table.row :key="$brand->id">
                    <flux:table.cell>
                        @if ($brand->imageUrl())
                            <img
                                src="{{ $brand->imageUrl() }}"
                                alt="{{ $brand->name }}"
                                class="size-10 rounded-lg object-contain"
                            />
                        @else
                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon.photo variant="micro" class="text-zinc-400" />
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell variant="strong">{{ $brand->name }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">{{ $brand->slug }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell align="center">{{ $brand->products_count }}</flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="pencil-square"
                            wire:click="edit({{ $brand->id }})"
                            :aria-label="__('Edit')"
                        />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="confirmDelete({{ $brand->id }})"
                            :aria-label="__('Delete')"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" align="center" class="py-10">
                        <flux:text>{{ __('No brands found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="brand-form" class="w-full max-w-lg" wire:close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $brandId ? __('Edit brand') : __('New brand') }}
                </flux:heading>
                <flux:subheading>{{ __('Name, slug and logo for this brand.') }}</flux:subheading>
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
                    :label="__('Logo')"
                    type="file"
                    accept="image/*"
                />

                <div wire:loading wire:target="image">
                    <flux:text size="sm" class="mt-2">{{ __('Uploading...') }}</flux:text>
                </div>

                @if ($image && ! $errors->has('image') && $image->isPreviewable())
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $image->temporaryUrl() }}" alt="" class="size-16 rounded-lg object-contain" />
                        <flux:text size="sm">{{ __('New logo selected.') }}</flux:text>
                    </div>
                @elseif ($currentImage)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $currentImage }}" alt="" class="size-16 rounded-lg object-contain" />
                        <flux:button size="sm" variant="subtle" type="button" wire:click="removeImage">
                            {{ __('Remove logo') }}
                        </flux:button>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-brand-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="brand-delete" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete brand?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Products keep their details but lose this brand. This cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete" data-test="confirm-delete-brand-button">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
