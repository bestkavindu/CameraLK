<?php

namespace Database\Seeders;

use App\Models\Brand;
use Database\Seeders\Support\PlaceholderImage;
use Database\Seeders\Support\SeedsImages;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    use SeedsImages;

    /**
     * Brands carried by the shop.
     *
     * @var list<array{name: string, slug: string}>
     */
    public const BRANDS = [
        ['name' => 'Autel', 'slug' => 'autel'],
        ['name' => 'Canon', 'slug' => 'canon'],
        ['name' => 'DJI', 'slug' => 'dji'],
        ['name' => 'Fujifilm', 'slug' => 'fujifilm'],
        ['name' => 'Godox', 'slug' => 'godox'],
        ['name' => 'GoPro', 'slug' => 'gopro'],
        ['name' => 'Insta360', 'slug' => 'insta360'],
        ['name' => 'Manfrotto', 'slug' => 'manfrotto'],
        ['name' => 'Nikon', 'slug' => 'nikon'],
        ['name' => 'Panasonic', 'slug' => 'panasonic'],
        ['name' => 'Peak Design', 'slug' => 'peak-design'],
        ['name' => 'SanDisk', 'slug' => 'sandisk'],
        ['name' => 'Sigma', 'slug' => 'sigma'],
        ['name' => 'Sony', 'slug' => 'sony'],
        ['name' => 'Tamron', 'slug' => 'tamron'],
        ['name' => 'Zhiyun', 'slug' => 'zhiyun'],
    ];

    /**
     * Seed the brands.
     */
    public function run(): void
    {
        foreach (self::BRANDS as $brand) {
            Brand::query()->updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'image' => $this->putImage(
                        "brands/{$brand['slug']}.jpg",
                        fn (): string => PlaceholderImage::monogram($brand['name'], $brand['name']),
                    ),
                ],
            );
        }
    }
}
