<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
});

test('guests are redirected from the brand page', function () {
    auth()->logout();

    $this->get(route('brands.index'))->assertRedirect(route('login'));
});

test('the brand page renders', function () {
    $this->get(route('brands.index'))->assertOk();
});

test('a brand can be created with a logo and a generated slug', function () {
    Livewire::test('pages::brands.index')
        ->call('create')
        ->set('name', 'Sony Alpha')
        ->assertSet('slug', 'sony-alpha')
        ->set('image', UploadedFile::fake()->image('sony.png'))
        ->call('save')
        ->assertHasNoErrors();

    $brand = Brand::sole();

    expect($brand->name)->toBe('Sony Alpha')
        ->and($brand->slug)->toBe('sony-alpha')
        ->and($brand->image)->toStartWith('brands/');

    Storage::disk('public')->assertExists($brand->image);
});

test('a brand slug must be unique', function () {
    Brand::create(['name' => 'Canon', 'slug' => 'canon']);

    Livewire::test('pages::brands.index')
        ->call('create')
        ->set('name', 'Canon')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

test('a brand can be updated and its old logo replaced', function () {
    $brand = Brand::create([
        'name' => 'Nikon',
        'slug' => 'nikon',
        'image' => UploadedFile::fake()->image('old.png')->store('brands', 'public'),
    ]);

    $old = $brand->image;

    Livewire::test('pages::brands.index')
        ->call('edit', $brand->id)
        ->assertSet('name', 'Nikon')
        ->set('name', 'Nikon Imaging')
        ->set('image', UploadedFile::fake()->image('new.png'))
        ->call('save')
        ->assertHasNoErrors();

    $brand->refresh();

    expect($brand->name)->toBe('Nikon Imaging')
        ->and($brand->slug)->toBe('nikon')
        ->and($brand->image)->not->toBe($old);

    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($brand->image);
});

test('brands can be searched', function () {
    Brand::create(['name' => 'Fujifilm', 'slug' => 'fujifilm']);
    Brand::create(['name' => 'Panasonic', 'slug' => 'panasonic']);

    Livewire::test('pages::brands.index')
        ->set('search', 'fuji')
        ->assertSee('Fujifilm')
        ->assertDontSee('Panasonic');
});

test('deleting a brand keeps its products but clears their brand', function () {
    $brand = Brand::create([
        'name' => 'Sigma',
        'slug' => 'sigma',
        'image' => UploadedFile::fake()->image('sigma.png')->store('brands', 'public'),
    ]);

    $category = Category::create(['name' => 'Lenses', 'slug' => 'lenses']);
    $subCategory = SubCategory::create(['category_id' => $category->id, 'name' => 'Prime', 'slug' => 'prime']);

    $product = Product::create([
        'brand_id' => $brand->id,
        'sub_category_id' => $subCategory->id,
        'name' => '35mm f/1.4',
        'base_price' => '899.00',
    ]);

    Livewire::test('pages::brands.index')
        ->call('confirmDelete', $brand->id)
        ->call('delete');

    expect(Brand::count())->toBe(0)
        ->and(Product::count())->toBe(1)
        ->and($product->refresh()->brand_id)->toBeNull();

    Storage::disk('public')->assertMissing($brand->image);
});

test('the brand product count is shown', function () {
    $brand = Brand::create(['name' => 'Sony', 'slug' => 'sony']);

    $category = Category::create(['name' => 'Cameras', 'slug' => 'cameras']);
    $subCategory = SubCategory::create(['category_id' => $category->id, 'name' => 'Mirrorless', 'slug' => 'mirrorless']);

    Product::create([
        'brand_id' => $brand->id,
        'sub_category_id' => $subCategory->id,
        'name' => 'A7 IV',
        'base_price' => '100',
    ]);

    Livewire::test('pages::brands.index')->assertSee('Sony');

    expect(Brand::withCount('products')->sole()->products_count)->toBe(1);
});
