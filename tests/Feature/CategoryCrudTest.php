<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
});

test('guests are redirected from the category pages', function () {
    auth()->logout();

    $this->get(route('categories.index'))->assertRedirect(route('login'));
    $this->get(route('sub-categories.index'))->assertRedirect(route('login'));
});

test('the category and sub category pages render', function () {
    $this->get(route('categories.index'))->assertOk();
    $this->get(route('sub-categories.index'))->assertOk();
});

test('a category can be created with an image and a generated slug', function () {
    Livewire::test('pages::categories.index')
        ->call('create')
        ->set('name', 'Mirrorless Cameras')
        ->assertSet('slug', 'mirrorless-cameras')
        ->set('image', UploadedFile::fake()->image('camera.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::sole();

    expect($category->name)->toBe('Mirrorless Cameras')
        ->and($category->slug)->toBe('mirrorless-cameras')
        ->and($category->image)->toStartWith('categories/');

    Storage::disk('public')->assertExists($category->image);
});

test('a category slug must be unique', function () {
    Category::create(['name' => 'Lenses', 'slug' => 'lenses']);

    Livewire::test('pages::categories.index')
        ->call('create')
        ->set('name', 'Lenses')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

test('a category can be updated and its old image replaced', function () {
    $category = Category::create([
        'name' => 'Tripods',
        'slug' => 'tripods',
        'image' => UploadedFile::fake()->image('old.jpg')->store('categories', 'public'),
    ]);

    $old = $category->image;

    Livewire::test('pages::categories.index')
        ->call('edit', $category->id)
        ->assertSet('name', 'Tripods')
        ->set('name', 'Tripods & Rigs')
        ->set('image', UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();

    expect($category->name)->toBe('Tripods & Rigs')
        ->and($category->slug)->toBe('tripods')
        ->and($category->image)->not->toBe($old);

    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($category->image);
});

test('deleting a category removes its image and sub categories', function () {
    $category = Category::create([
        'name' => 'Bags',
        'slug' => 'bags',
        'image' => UploadedFile::fake()->image('bag.jpg')->store('categories', 'public'),
    ]);

    SubCategory::create(['category_id' => $category->id, 'name' => 'Sling', 'slug' => 'sling']);

    Livewire::test('pages::categories.index')
        ->call('confirmDelete', $category->id)
        ->call('delete');

    expect(Category::count())->toBe(0)
        ->and(SubCategory::count())->toBe(0);

    Storage::disk('public')->assertMissing($category->image);
});

test('a sub category can be created under a main category', function () {
    $category = Category::create(['name' => 'Lighting', 'slug' => 'lighting']);

    Livewire::test('pages::sub-categories.index')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Softboxes')
        ->assertSet('slug', 'softboxes')
        ->set('image', UploadedFile::fake()->image('softbox.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $subCategory = SubCategory::sole();

    expect($subCategory->category_id)->toBe($category->id)
        ->and($subCategory->slug)->toBe('softboxes')
        ->and($subCategory->image)->toStartWith('sub-categories/');

    Storage::disk('public')->assertExists($subCategory->image);
});

test('a sub category requires an existing main category', function () {
    Livewire::test('pages::sub-categories.index')
        ->call('create')
        ->set('name', 'Orphan')
        ->call('save')
        ->assertHasErrors(['categoryId' => 'required']);
});

test('sub categories can be filtered by main category and searched', function () {
    $lenses = Category::create(['name' => 'Lenses', 'slug' => 'lenses']);
    $bodies = Category::create(['name' => 'Bodies', 'slug' => 'bodies']);

    SubCategory::create(['category_id' => $lenses->id, 'name' => 'Prime', 'slug' => 'prime']);
    SubCategory::create(['category_id' => $bodies->id, 'name' => 'Full Frame', 'slug' => 'full-frame']);

    Livewire::test('pages::sub-categories.index')
        ->set('filterCategory', (string) $lenses->id)
        ->assertSee('Prime')
        ->assertDontSee('Full Frame')
        ->set('filterCategory', '')
        ->set('search', 'full')
        ->assertSee('Full Frame')
        ->assertDontSee('Prime');
});

test('a sub category can be updated and deleted', function () {
    $category = Category::create(['name' => 'Audio', 'slug' => 'audio']);

    $subCategory = SubCategory::create([
        'category_id' => $category->id,
        'name' => 'Shotgun',
        'slug' => 'shotgun',
        'image' => UploadedFile::fake()->image('mic.jpg')->store('sub-categories', 'public'),
    ]);

    Livewire::test('pages::sub-categories.index')
        ->call('edit', $subCategory->id)
        ->set('name', 'Shotgun Mics')
        ->call('save')
        ->assertHasNoErrors()
        ->call('confirmDelete', $subCategory->id)
        ->call('delete');

    expect(SubCategory::count())->toBe(0);

    Storage::disk('public')->assertMissing($subCategory->image);
});
