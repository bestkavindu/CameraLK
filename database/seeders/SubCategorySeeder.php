<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Database\Seeders\Support\PlaceholderImage;
use Database\Seeders\Support\SeedsImages;
use Illuminate\Database\Seeder;
use RuntimeException;

class SubCategorySeeder extends Seeder
{
    use SeedsImages;

    /**
     * Sub categories grouped under the slug of their main category.
     *
     * @var array<string, list<array{name: string, slug: string}>>
     */
    private const SUB_CATEGORIES = [
        'cameras' => [
            ['name' => 'Mirrorless', 'slug' => 'mirrorless'],
            ['name' => 'Compact', 'slug' => 'compact'],
            ['name' => 'Action & 360', 'slug' => 'action-360'],
        ],
        'drones' => [
            ['name' => 'Consumer Drones', 'slug' => 'consumer-drones'],
            ['name' => 'FPV', 'slug' => 'fpv'],
            ['name' => 'Drone Accessories', 'slug' => 'drone-accessories'],
        ],
        'lenses' => [
            ['name' => 'Prime Lenses', 'slug' => 'prime-lenses'],
            ['name' => 'Zoom Lenses', 'slug' => 'zoom-lenses'],
        ],
        'gimbals' => [
            ['name' => 'Camera Gimbals', 'slug' => 'camera-gimbals'],
            ['name' => 'Phone Gimbals', 'slug' => 'phone-gimbals'],
        ],
        'accessories' => [
            ['name' => 'Bags & Cases', 'slug' => 'bags-cases'],
            ['name' => 'Lighting', 'slug' => 'lighting'],
            ['name' => 'Memory & Storage', 'slug' => 'memory-storage'],
            ['name' => 'Tripods & Supports', 'slug' => 'tripods-supports'],
        ],
    ];

    /**
     * Seed the sub categories.
     */
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'slug');

        foreach (self::SUB_CATEGORIES as $categorySlug => $subCategories) {
            $categoryId = $categories->get($categorySlug);

            if ($categoryId === null) {
                throw new RuntimeException("Missing category [{$categorySlug}]. Run the CategorySeeder first.");
            }

            foreach ($subCategories as $subCategory) {
                SubCategory::query()->updateOrCreate(
                    ['slug' => $subCategory['slug']],
                    [
                        'category_id' => $categoryId,
                        'name' => $subCategory['name'],
                        'image' => $this->putImage(
                            "sub-categories/{$subCategory['slug']}.jpg",
                            fn (): string => PlaceholderImage::monogram($subCategory['name'], $subCategory['name']),
                        ),
                    ],
                );
            }
        }
    }
}
