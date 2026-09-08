<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Livewire\Livewire;

beforeEach(function () {
    $this->cameras = Category::create(['name' => 'Cameras', 'slug' => 'cameras']);
    $this->drones = Category::create(['name' => 'Drones', 'slug' => 'drones']);

    $this->mirrorless = SubCategory::create([
        'category_id' => $this->cameras->id,
        'name' => 'Mirrorless',
        'slug' => 'mirrorless',
    ]);

    $this->folding = SubCategory::create([
        'category_id' => $this->drones->id,
        'name' => 'Folding',
        'slug' => 'folding',
    ]);

    $this->sony = Brand::create(['name' => 'Sony', 'slug' => 'sony']);
    $this->dji = Brand::create(['name' => 'DJI', 'slug' => 'dji']);
});

/**
 * Create a storefront product directly, bypassing the admin form.
 */
function makeStoreProduct(SubCategory $subCategory, array $attributes = []): Product
{
    return Product::create(array_merge([
        'sub_category_id' => $subCategory->id,
        'name' => 'Sample Camera',
        'base_price' => '1000.00',
    ], $attributes));
}

test('the storefront home page renders the navbar and footer for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('SHUTTER')
        ->assertSee('All rights reserved.', escape: false)
        ->assertSee(route('store.products'));
});

test('the products page renders for guests', function () {
    makeStoreProduct($this->mirrorless, [
        'brand_id' => $this->sony->id,
        'name' => 'Alpha 7 IV',
        'base_price' => '1249000.00',
        'discount_price' => '1124000.00',
        'description' => '<p>33MP full-frame hybrid with 10-bit 4K60.</p>',
    ]);

    $this->get(route('store.products'))
        ->assertOk()
        ->assertSee('Alpha 7 IV')
        ->assertSee('Sony')
        ->assertSee('Rs 1,124,000')
        ->assertSee('Rs 1,249,000')
        ->assertSee('-10% off')
        ->assertSee('33MP full-frame hybrid with 10-bit 4K60.');
});

test('the description is shown as plain text without its stored markup', function () {
    makeStoreProduct($this->mirrorless, [
        'description' => '<p>Weather-sealed <strong>magnesium</strong> build.</p>',
    ]);

    Livewire::test('pages::store.products')
        ->assertSee('Weather-sealed magnesium build.')
        ->assertDontSee('<strong>', escape: false);
});

test('adjacent blocks in a description do not run together', function () {
    makeStoreProduct($this->mirrorless, [
        'description' => '<p>Retro-styled body.</p><p>Stocked in Colombo.</p>',
    ]);

    Livewire::test('pages::store.products')
        ->assertSee('Retro-styled body. Stocked in Colombo.')
        ->assertDontSee('body.Stocked');
});

test('list items in a description are spaced apart', function () {
    makeStoreProduct($this->mirrorless, [
        'description' => '<ul><li>Weather sealed</li><li>Dual card slots</li></ul>',
    ]);

    Livewire::test('pages::store.products')
        ->assertSee('Weather sealed Dual card slots')
        ->assertDontSee('sealedDual');
});

test('the price hides empty cents but keeps real ones', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Round', 'base_price' => '42900.00']);
    makeStoreProduct($this->mirrorless, ['name' => 'Fractional', 'base_price' => '42900.50']);

    Livewire::test('pages::store.products')
        ->assertSee('Rs 42,900')
        ->assertSee('Rs 42,900.50');
});

test('searching narrows the grid across names, brands and categories', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Alpha 7 IV', 'brand_id' => $this->sony->id]);
    makeStoreProduct($this->folding, ['name' => 'Mini 4 Pro', 'brand_id' => $this->dji->id]);

    Livewire::test('pages::store.products')
        ->set('search', 'mini')
        ->assertSee('Mini 4 Pro')
        ->assertDontSee('Alpha 7 IV')
        ->set('search', 'sony')
        ->assertSee('Alpha 7 IV')
        ->assertDontSee('Mini 4 Pro')
        ->set('search', 'drones')
        ->assertSee('Mini 4 Pro')
        ->assertDontSee('Alpha 7 IV');
});

test('the category chips filter the grid and carry counts', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Alpha 7 IV']);
    makeStoreProduct($this->mirrorless, ['name' => 'X-T5']);
    makeStoreProduct($this->folding, ['name' => 'Mini 4 Pro']);

    $component = Livewire::test('pages::store.products');

    $chips = $component->instance()->categoryChips->keyBy('slug');

    expect($chips['']['count'])->toBe(3)
        ->and($chips['']['active'])->toBeTrue()
        ->and($chips['cameras']['count'])->toBe(2)
        ->and($chips['drones']['count'])->toBe(1);

    $component->call('selectCategory', 'drones')
        ->assertSee('Mini 4 Pro')
        ->assertDontSee('Alpha 7 IV')
        ->assertSet('category', 'drones');

    expect($component->instance()->categoryChips->keyBy('slug')['drones']['active'])->toBeTrue();
});

test('the chip counts respect the active search and brand filters', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Alpha 7 IV', 'brand_id' => $this->sony->id]);
    makeStoreProduct($this->mirrorless, ['name' => 'X-T5']);
    makeStoreProduct($this->folding, ['name' => 'Mini 4 Pro', 'brand_id' => $this->dji->id]);

    $chips = Livewire::test('pages::store.products')
        ->set('brand', 'sony')
        ->instance()
        ->categoryChips
        ->keyBy('slug');

    expect($chips['']['count'])->toBe(1)
        ->and($chips['cameras']['count'])->toBe(1)
        ->and($chips['drones']['count'])->toBe(0);
});

test('the brand filter only offers brands that have products', function () {
    makeStoreProduct($this->mirrorless, ['brand_id' => $this->sony->id]);

    $options = Livewire::test('pages::store.products')->instance()->brandOptions;

    expect($options->keys()->all())->toBe(['sony']);
});

test('the brand filter narrows the grid', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Alpha 7 IV', 'brand_id' => $this->sony->id]);
    makeStoreProduct($this->folding, ['name' => 'Mini 4 Pro', 'brand_id' => $this->dji->id]);

    Livewire::test('pages::store.products')
        ->set('brand', 'dji')
        ->assertSee('Mini 4 Pro')
        ->assertDontSee('Alpha 7 IV');
});

test('products can be sorted by effective price, discount and name', function () {
    makeStoreProduct($this->mirrorless, [
        'name' => 'Mid',
        'base_price' => '500.00',
    ]);
    makeStoreProduct($this->mirrorless, [
        'name' => 'Cheap after discount',
        'base_price' => '900.00',
        'discount_price' => '100.00',
    ]);
    makeStoreProduct($this->mirrorless, [
        'name' => 'Expensive',
        'base_price' => '900.00',
    ]);

    $names = fn (string $sort): array => Livewire::test('pages::store.products')
        ->set('sort', $sort)
        ->instance()
        ->products
        ->pluck('name')
        ->all();

    expect($names('price-asc'))->toBe(['Cheap after discount', 'Mid', 'Expensive'])
        ->and($names('price-desc'))->toBe(['Expensive', 'Mid', 'Cheap after discount'])
        ->and($names('discount'))->toBe(['Cheap after discount', 'Expensive', 'Mid'])
        ->and($names('name'))->toBe(['Cheap after discount', 'Expensive', 'Mid']);
});

test('an unknown sort value falls back to featured order', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'First']);
    makeStoreProduct($this->mirrorless, ['name' => 'Second']);

    $names = Livewire::test('pages::store.products')
        ->set('sort', 'nonsense')
        ->instance()
        ->products
        ->pluck('name')
        ->all();

    expect($names)->toBe(['Second', 'First']);
});

test('load more grows the grid a page at a time', function () {
    foreach (range(1, 15) as $index) {
        makeStoreProduct($this->mirrorless, ['name' => "Camera {$index}"]);
    }

    $component = Livewire::test('pages::store.products');

    expect($component->instance()->products)->toHaveCount(12)
        ->and($component->instance()->remaining)->toBe(3)
        ->and($component->instance()->nextPageSize)->toBe(3);

    $component->assertSee('Show 3 more')
        ->call('loadMore');

    expect($component->instance()->products)->toHaveCount(15)
        ->and($component->instance()->remaining)->toBe(0);

    $component->assertDontSee('Show 3 more');
});

test('changing a filter collapses the grid back to one page', function () {
    foreach (range(1, 15) as $index) {
        makeStoreProduct($this->mirrorless, ['name' => "Camera {$index}"]);
    }

    $component = Livewire::test('pages::store.products')
        ->call('loadMore')
        ->assertSet('shown', 24)
        ->set('search', 'camera')
        ->assertSet('shown', 12);

    $component->call('loadMore')
        ->call('selectCategory', 'cameras')
        ->assertSet('shown', 12);
});

test('an unmatched search shows the empty state and resetting brings the catalogue back', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Alpha 7 IV']);

    Livewire::test('pages::store.products')
        ->set('search', 'nothing at all')
        ->assertSee('No gear matched that')
        ->assertDontSee('Alpha 7 IV')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSee('Alpha 7 IV')
        ->assertDontSee('No gear matched that');
});

test('the result line reports the total and whether filters are active', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Alpha 7 IV']);
    makeStoreProduct($this->folding, ['name' => 'Mini 4 Pro']);

    Livewire::test('pages::store.products')
        ->assertSee('in stock')
        ->assertSeeInOrder(['2', 'products', 'in stock'])
        ->call('selectCategory', 'drones')
        ->assertSee('match your filters')
        ->assertSeeInOrder(['1', 'product', 'match your filters']);
});

test('the filters survive a page load from the query string', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Alpha 7 IV', 'brand_id' => $this->sony->id]);
    makeStoreProduct($this->folding, ['name' => 'Mini 4 Pro', 'brand_id' => $this->dji->id]);

    $this->get(route('store.products', ['category' => 'drones', 'brand' => 'dji', 'sort' => 'price-asc']))
        ->assertOk()
        ->assertSee('Mini 4 Pro');

    // Scoped to the component rather than the whole page: the navbar's "new in
    // stock" preview lists the newest products site-wide, so a page-level
    // assertDontSee would match that instead of the grid it means to check.
    Livewire::withQueryParams(['category' => 'drones', 'brand' => 'dji', 'sort' => 'price-asc'])
        ->test('pages::store.products')
        ->assertSee('Mini 4 Pro')
        ->assertDontSee('Alpha 7 IV');
});

test('a product without a brand or image still renders', function () {
    makeStoreProduct($this->mirrorless, ['name' => 'Unbranded Body']);

    Livewire::test('pages::store.products')
        ->assertSee('Unbranded Body')
        ->assertSee('Mirrorless');
});
