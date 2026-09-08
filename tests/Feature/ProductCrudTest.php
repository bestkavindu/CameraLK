<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create(['email_verified_at' => now()]));

    $this->category = Category::create(['name' => 'Cameras', 'slug' => 'cameras']);

    $this->subCategory = SubCategory::create([
        'category_id' => $this->category->id,
        'name' => 'Mirrorless',
        'slug' => 'mirrorless',
    ]);

    $this->brand = Brand::create(['name' => 'Sony', 'slug' => 'sony']);
});

/**
 * Create a product directly, bypassing the form.
 */
function makeProduct(SubCategory $subCategory, array $attributes = []): Product
{
    return Product::create(array_merge([
        'sub_category_id' => $subCategory->id,
        'name' => 'Sample Camera',
        'base_price' => '1000.00',
        'key_features' => ['4K 240fps slow motion'],
    ], $attributes));
}

test('guests are redirected from the product pages', function () {
    $product = makeProduct($this->subCategory);

    auth()->logout();

    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('products.create'))->assertRedirect(route('login'));
    $this->get(route('products.edit', $product))->assertRedirect(route('login'));
});

test('the product pages render', function () {
    $product = makeProduct($this->subCategory);

    $this->get(route('products.index'))->assertOk()->assertSee('Sample Camera');
    $this->get(route('products.create'))->assertOk();
    $this->get(route('products.edit', $product))->assertOk()->assertSee('Sample Camera');
});

test('the description field mounts the rich text editor', function () {
    $product = makeProduct($this->subCategory, ['description' => '<p>Existing copy.</p>']);

    $this->get(route('products.create'))
        ->assertOk()
        // The Alpine component registered in resources/js/app.js...
        ->assertSee('richTextEditor(')
        ->assertSee('wire:ignore', escape: false);

    // Editing seeds the editor with the stored description rather than an empty document...
    $this->get(route('products.edit', $product))
        ->assertOk()
        ->assertSee('Existing copy.');
});

test('a product can be created with images, features and a description', function () {
    Livewire::test('pages::products.form')
        ->set('name', 'Alpha 7 IV')
        ->set('brandId', (string) $this->brand->id)
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $this->subCategory->id)
        ->set('basePrice', '2499.99')
        ->set('discountPrice', '2199.00')
        ->set('description', '<p>A <strong>full frame</strong> hybrid camera.</p>')
        ->set('keyFeatures', ['4K 240fps slow motion', '33MP sensor', '  '])
        ->set('newImages', [
            UploadedFile::fake()->image('front.jpg'),
            UploadedFile::fake()->image('back.jpg'),
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('products.index'));

    $product = Product::with('images')->sole();

    expect($product->name)->toBe('Alpha 7 IV')
        ->and($product->brand_id)->toBe($this->brand->id)
        ->and($product->sub_category_id)->toBe($this->subCategory->id)
        ->and((float) $product->base_price)->toBe(2499.99)
        ->and((float) $product->discount_price)->toBe(2199.0)
        ->and($product->description)->toBe('<p>A <strong>full frame</strong> hybrid camera.</p>')
        // Blank feature lines are dropped...
        ->and($product->key_features)->toBe(['4K 240fps slow motion', '33MP sensor'])
        ->and($product->images)->toHaveCount(2)
        ->and($product->images->pluck('sort_order')->all())->toBe([1, 2]);

    foreach ($product->images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

test('the description is stripped of unsafe markup', function () {
    Livewire::test('pages::products.form')
        ->set('name', 'Rig')
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $this->subCategory->id)
        ->set('basePrice', '100')
        ->set('description', '<p onclick="steal()">Safe <script>alert(1)</script><em>text</em></p><iframe src="//evil"></iframe>')
        ->call('save')
        ->assertHasNoErrors();

    $description = Product::sole()->description;

    expect($description)->toBe('<p>Safe <em>text</em></p>')
        ->and($description)->not->toContain('script')
        ->and($description)->not->toContain('onclick')
        ->and($description)->not->toContain('iframe');
});

test('uploaded files must be images within the size limit', function () {
    Livewire::test('pages::products.form')
        ->set('name', 'Bad upload')
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $this->subCategory->id)
        ->set('basePrice', '100')
        ->set('newImages', [UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf')])
        ->call('save')
        ->assertHasErrors(['newImages.0' => 'image']);

    Livewire::test('pages::products.form')
        ->set('name', 'Too big')
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $this->subCategory->id)
        ->set('basePrice', '100')
        ->set('newImages', [UploadedFile::fake()->image('huge.jpg')->size(3000)])
        ->call('save')
        ->assertHasErrors(['newImages.0' => 'max']);

    expect(Product::count())->toBe(0);
});

test('the discount price must be lower than the base price', function () {
    Livewire::test('pages::products.form')
        ->set('name', 'Lens')
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $this->subCategory->id)
        ->set('basePrice', '500')
        ->set('discountPrice', '600')
        ->call('save')
        ->assertHasErrors(['discountPrice' => 'lt']);

    expect(Product::count())->toBe(0);
});

test('the sub category must belong to the chosen category', function () {
    $other = Category::create(['name' => 'Audio', 'slug' => 'audio']);

    $foreign = SubCategory::create([
        'category_id' => $other->id,
        'name' => 'Microphones',
        'slug' => 'microphones',
    ]);

    Livewire::test('pages::products.form')
        ->set('name', 'Mismatch')
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $foreign->id)
        ->set('basePrice', '100')
        ->call('save')
        ->assertHasErrors(['subCategoryId' => 'exists']);
});

test('changing the category clears the selected sub category', function () {
    Livewire::test('pages::products.form')
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $this->subCategory->id)
        ->set('categoryId', (string) Category::create(['name' => 'Bags', 'slug' => 'bags'])->id)
        ->assertSet('subCategoryId', '');
});

test('the edit form is populated from the product', function () {
    $product = makeProduct($this->subCategory, [
        'discount_price' => '899.00',
        'description' => '<p>Existing copy.</p>',
    ]);

    Livewire::test('pages::products.form', ['product' => $product])
        ->assertSet('name', 'Sample Camera')
        ->assertSet('categoryId', (string) $this->category->id)
        ->assertSet('subCategoryId', (string) $this->subCategory->id)
        ->assertSet('description', '<p>Existing copy.</p>')
        ->assertSet('keyFeatures', ['4K 240fps slow motion']);
});

test('a product can be updated and gain new images', function () {
    $product = makeProduct($this->subCategory);

    $product->images()->create([
        'path' => UploadedFile::fake()->image('old.jpg')->store('products', 'public'),
        'sort_order' => 1,
    ]);

    Livewire::test('pages::products.form', ['product' => $product])
        ->set('name', 'Sample Camera II')
        ->set('discountPrice', '850')
        ->set('newImages', [UploadedFile::fake()->image('extra.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    $product->refresh()->load('images');

    expect($product->name)->toBe('Sample Camera II')
        ->and((float) $product->discount_price)->toBe(850.0)
        ->and($product->images)->toHaveCount(2)
        ->and($product->images->pluck('sort_order')->all())->toBe([1, 2]);
});

test('staged image removals only apply on save', function () {
    $product = makeProduct($this->subCategory);

    $image = $product->images()->create([
        'path' => UploadedFile::fake()->image('doomed.jpg')->store('products', 'public'),
        'sort_order' => 1,
    ]);

    $component = Livewire::test('pages::products.form', ['product' => $product])
        ->call('removeExistingImage', $image->id);

    // Still on disk and in the database until the form is submitted...
    Storage::disk('public')->assertExists($image->path);
    expect(ProductImage::count())->toBe(1);

    $component->call('restoreImages')->assertSet('removeImageIds', []);

    $component->call('removeExistingImage', $image->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(ProductImage::count())->toBe(0);
    Storage::disk('public')->assertMissing($image->path);
});

test('key feature rows can be added and removed', function () {
    Livewire::test('pages::products.form')
        ->assertSet('keyFeatures', [''])
        ->call('addFeature')
        ->set('keyFeatures', ['4K 240fps slow motion', '5-axis stabilisation'])
        ->call('removeFeature', 0)
        ->assertSet('keyFeatures', ['5-axis stabilisation'])
        ->call('removeFeature', 0)
        // Always leaves one empty input behind...
        ->assertSet('keyFeatures', ['']);
});

test('products can be searched and filtered by category', function () {
    $audio = Category::create(['name' => 'Audio', 'slug' => 'audio']);
    $mics = SubCategory::create(['category_id' => $audio->id, 'name' => 'Microphones', 'slug' => 'microphones']);

    makeProduct($this->subCategory, ['name' => 'Alpha 7 IV']);
    makeProduct($mics, ['name' => 'Shotgun Mic']);

    Livewire::test('pages::products.index')
        ->set('filterCategory', (string) $this->category->id)
        ->assertSee('Alpha 7 IV')
        ->assertDontSee('Shotgun Mic')
        ->set('filterCategory', '')
        ->set('search', 'shotgun')
        ->assertSee('Shotgun Mic')
        ->assertDontSee('Alpha 7 IV');
});

test('deleting a product removes its images from disk', function () {
    $product = makeProduct($this->subCategory);

    $paths = collect([
        UploadedFile::fake()->image('one.jpg')->store('products', 'public'),
        UploadedFile::fake()->image('two.jpg')->store('products', 'public'),
    ])->each(fn (string $path, int $i) => $product->images()->create(['path' => $path, 'sort_order' => $i]));

    Livewire::test('pages::products.index')
        ->call('confirmDelete', $product->id)
        ->call('delete');

    expect(Product::count())->toBe(0)
        ->and(ProductImage::count())->toBe(0);

    $paths->each(fn (string $path) => Storage::disk('public')->assertMissing($path));
});

test('deleting a sub category cascades to its products and images', function () {
    $product = makeProduct($this->subCategory);

    $product->images()->create(['path' => 'products/x.jpg', 'sort_order' => 1]);

    $this->subCategory->delete();

    expect(Product::count())->toBe(0)
        ->and(ProductImage::count())->toBe(0);
});

test('a product can be saved without a brand', function () {
    Livewire::test('pages::products.form')
        ->set('name', 'Unbranded rig')
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $this->subCategory->id)
        ->set('basePrice', '100')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::sole()->brand_id)->toBeNull();
});

test('the brand must exist', function () {
    Livewire::test('pages::products.form')
        ->set('name', 'Ghost brand')
        ->set('brandId', '9999')
        ->set('categoryId', (string) $this->category->id)
        ->set('subCategoryId', (string) $this->subCategory->id)
        ->set('basePrice', '100')
        ->call('save')
        ->assertHasErrors(['brandId' => 'exists']);

    expect(Product::count())->toBe(0);
});

test('the edit form is populated with the existing brand', function () {
    $product = makeProduct($this->subCategory, ['brand_id' => $this->brand->id]);

    Livewire::test('pages::products.form', ['product' => $product])
        ->assertSet('brandId', (string) $this->brand->id);
});

test('a brand can be cleared from a product', function () {
    $product = makeProduct($this->subCategory, ['brand_id' => $this->brand->id]);

    Livewire::test('pages::products.form', ['product' => $product])
        ->set('brandId', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh()->brand_id)->toBeNull();
});

test('products can be filtered by brand', function () {
    $canon = Brand::create(['name' => 'Canon', 'slug' => 'canon']);

    makeProduct($this->subCategory, ['name' => 'Alpha 7 IV', 'brand_id' => $this->brand->id]);
    makeProduct($this->subCategory, ['name' => 'EOS R6', 'brand_id' => $canon->id]);

    Livewire::test('pages::products.index')
        ->set('filterBrand', (string) $canon->id)
        ->assertSee('EOS R6')
        ->assertDontSee('Alpha 7 IV');
});

test('the product list shows the brand name', function () {
    makeProduct($this->subCategory, ['name' => 'Alpha 7 IV', 'brand_id' => $this->brand->id]);

    Livewire::test('pages::products.index')
        ->assertSee('Alpha 7 IV')
        ->assertSee('Sony');
});

test('the discount percentage is derived from the two prices', function () {
    $product = makeProduct($this->subCategory, ['base_price' => '1000.00', 'discount_price' => '750.00']);

    $undiscounted = makeProduct($this->subCategory, ['base_price' => '1000.00']);

    expect($product->discountPercentage())->toBe(25)
        ->and($product->effectivePrice())->toBe('750.00')
        ->and($undiscounted->discountPercentage())->toBeNull()
        ->and($undiscounted->effectivePrice())->toBe('1000.00');
});

test('a failed upload reports a readable field name', function () {
    // Reproduce exactly what Livewire's upload endpoint does when PHP rejects
    // the file before it reaches our own rules (UPLOAD_ERR_INI_SIZE), then the
    // rewrite Livewire applies to map `files.*` onto the bound property.
    $rejected = new UploadedFile(
        UploadedFile::fake()->image('huge.png')->getPathname(),
        'huge.png',
        'image/png',
        UPLOAD_ERR_INI_SIZE,
        true,
    );

    $validator = Validator::make(
        ['files' => [$rejected]],
        ['files.*' => ['required', 'file', 'max:12288']],
    );

    expect($validator->fails())->toBeTrue();

    $json = json_encode(['errors' => $validator->errors()->toArray()]);

    $errors = json_decode(str_ireplace('files', 'newImages', $json), true)['errors'];

    expect(array_keys($errors))->toBe(['newImages.0'])
        ->and($errors['newImages.0'][0])->toBe('The image failed to upload.')
        ->and($errors['newImages.0'][0])->not->toContain('newImages.0');
});

test('camel case form fields read naturally in validation messages', function () {
    $component = Livewire::test('pages::products.form')->call('save');

    $errors = $component->errors()->getMessages();

    expect($errors['name'][0])->toContain('name')
        ->and($errors['subCategoryId'][0])->toContain('sub category')
        ->and($errors['subCategoryId'][0])->not->toContain('subCategoryId')
        ->and($errors['basePrice'][0])->toContain('base price')
        ->and($errors['basePrice'][0])->not->toContain('basePrice');
});

test('the discount hint renders after the input so the price fields align', function () {
    $html = $this->get(route('products.create'))->assertOk()->getContent();

    $hint = 'Optional. Must be lower than the base price.';

    // Flux also echoes label/description back as raw attributes on the input, so
    // match the rendered <ui-description> element rather than the bare text.
    preg_match_all('/<ui-description(?:\s[^>]*)?>(.*?)<\/ui-description>/s', $html, $matches, PREG_OFFSET_CAPTURE);

    $rendered = array_values(array_filter(
        $matches[0],
        fn (array $m) => str_contains($m[0], $hint),
    ));

    // Exactly one: a second, leading description would push the input down and
    // misalign the two price columns again.
    expect($rendered)->toHaveCount(1);

    $inputPos = strpos($html, 'wire:model="discountPrice"');

    expect($inputPos)->not->toBeFalse()
        ->and($rendered[0][1])->toBeGreaterThan($inputPos);
});
