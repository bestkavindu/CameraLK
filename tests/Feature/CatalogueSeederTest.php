<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use Database\Seeders\BrandSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SubCategorySeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Run the taxonomy seeders products depend on.
 */
function seedTaxonomy(): void
{
    test()->seed(CategorySeeder::class);
    test()->seed(SubCategorySeeder::class);
    test()->seed(BrandSeeder::class);
}

test('the taxonomy seeders create categories, sub categories and brands with images', function () {
    seedTaxonomy();

    expect(Category::count())->toBe(5)
        ->and(SubCategory::count())->toBe(14)
        ->and(Brand::count())->toBe(16);

    expect(Category::pluck('slug')->all())->toContain('cameras', 'drones', 'lenses', 'gimbals', 'accessories');

    // Every record carries an image, and the file behind it really exists.
    foreach ([Category::all(), SubCategory::all(), Brand::all()] as $records) {
        foreach ($records as $record) {
            expect($record->image)->not->toBeNull()
                ->and(Storage::disk('public')->exists($record->image))->toBeTrue();
        }
    }

    // Sub categories hang off the right parent.
    expect(SubCategory::where('slug', 'mirrorless')->first()->category->slug)->toBe('cameras')
        ->and(SubCategory::where('slug', 'fpv')->first()->category->slug)->toBe('drones');
});

test('the taxonomy seeders can be re-run without duplicating records', function () {
    seedTaxonomy();
    seedTaxonomy();

    expect(Category::count())->toBe(5)
        ->and(SubCategory::count())->toBe(14)
        ->and(Brand::count())->toBe(16);
});

test('the sub category seeder refuses to run before the categories exist', function () {
    expect(fn () => test()->seed(SubCategorySeeder::class))
        ->toThrow(RuntimeException::class, 'Run the CategorySeeder first');
});

test('the product seeder refuses to run before the sub categories exist', function () {
    test()->seed(CategorySeeder::class);

    expect(fn () => test()->seed(ProductSeeder::class))
        ->toThrow(RuntimeException::class, 'Run the SubCategorySeeder first');
});

/*
 * The product seeder draws roughly a hundred images, so it is asserted once in
 * a single pass rather than re-run per expectation. Excluded with
 * `--exclude-group slow` when a fast loop matters.
 */
test('the product seeder builds the full catalogue', function () {
    seedTaxonomy();
    test()->seed(ProductSeeder::class);

    expect(Product::count())->toBe(40)
        ->and(Product::doesntHave('images')->count())->toBe(0);

    $disk = Storage::disk('public');

    foreach (Product::with('images')->get() as $product) {
        // One to four images each, present on disk and ordered from one.
        expect($product->images->count())->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(4)
            ->and($product->images->pluck('sort_order')->all())->toBe(range(1, $product->images->count()));

        foreach ($product->images as $image) {
            expect($disk->exists($image->path))->toBeTrue();
        }

        expect((float) $product->base_price)->toBeGreaterThan(0.0)
            ->and($product->description)->toStartWith('<p>')
            ->and($product->key_features)->toBeArray()
            ->and(count($product->key_features))->toBeGreaterThanOrEqual(3)
            ->and($product->subCategory)->not->toBeNull();

        if ($product->discount_price !== null) {
            // A discount must actually be a discount, and a believable one.
            expect((float) $product->discount_price)->toBeLessThan((float) $product->base_price)
                ->and($product->discountPercentage())->toBeGreaterThanOrEqual(4)
                ->toBeLessThanOrEqual(23);
        }
    }

    // The mix matters: an all-or-nothing sale would hide bugs in the
    // storefront's badge and strikethrough handling.
    expect(Product::whereNotNull('discount_price')->count())->toBeGreaterThan(10)
        ->and(Product::whereNull('discount_price')->count())->toBeGreaterThan(3);

    // One deliberately unbranded product, so the storefront fallback is covered.
    expect(Product::whereNull('brand_id')->count())->toBe(1);

    // Re-running must not duplicate products or re-attach images.
    $products = Product::count();
    $images = ProductImage::count();

    test()->seed(ProductSeeder::class);

    expect(Product::count())->toBe($products)
        ->and(ProductImage::count())->toBe($images);

    // Finally, the storefront built on this data. Featured order depends on the
    // randomised created_at, so assert the page shape rather than which
    // products happen to land on the first page.
    $response = test()->get(route('store.products'))
        ->assertOk()
        ->assertSee('Show 12 more');

    expect(substr_count($response->getContent(), '<article'))->toBe(12);

    // Any given product is still reachable through the search.
    Livewire::test('pages::store.products')
        ->set('search', 'Alpha 7 IV')
        ->assertSee('Alpha 7 IV');

    $chips = Livewire::test('pages::store.products')->instance()->categoryChips;

    expect($chips->firstWhere('slug', '')['count'])->toBe(40)
        ->and($chips->where('slug', '!=', '')->sum('count'))->toBe(40);
})->group('slow');
