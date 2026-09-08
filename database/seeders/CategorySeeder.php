<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Support\PlaceholderImage;
use Database\Seeders\Support\SeedsImages;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use SeedsImages;

    /**
     * The main categories the storefront chips are built from.
     *
     * @var list<array{name: string, slug: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Cameras', 'slug' => 'cameras'],
        ['name' => 'Drones', 'slug' => 'drones'],
        ['name' => 'Lenses', 'slug' => 'lenses'],
        ['name' => 'Gimbals', 'slug' => 'gimbals'],
        ['name' => 'Accessories', 'slug' => 'accessories'],
    ];

    /**
     * Seed the main categories.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'image' => $this->putImage(
                        "categories/{$category['slug']}.jpg",
                        fn (): string => PlaceholderImage::monogram($category['name'], $category['name']),
                    ),
                ],
            );
        }
    }
}
