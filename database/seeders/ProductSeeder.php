<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\SubCategory;
use App\Support\HtmlSanitizer;
use Database\Seeders\Support\PlaceholderImage;
use Database\Seeders\Support\SeedsImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

class ProductSeeder extends Seeder
{
    use SeedsImages;

    /**
     * Fixed seed for the randomizer.
     *
     * Prices, discounts and image counts are randomised, but from a fixed seed
     * so that re-running the seeder produces the same catalogue rather than a
     * different one every time.
     */
    private const RANDOM_SEED = 20260819;

    /**
     * The catalogue.
     *
     * Prices are the pre-discount rupee figure; the discount itself is rolled
     * per product below. A null brand is deliberate, so the storefront's
     * unbranded path gets exercised.
     *
     * @var list<array{name: string, brand: string|null, sub: string, price: int, blurb: string}>
     */
    private const PRODUCTS = [
        // Cameras — Mirrorless.
        ['name' => 'Alpha 7 IV', 'brand' => 'sony', 'sub' => 'mirrorless', 'price' => 1249000, 'blurb' => '33MP full-frame hybrid with 10-bit 4K60 and dual card slots.'],
        ['name' => 'EOS R6 Mark II', 'brand' => 'canon', 'sub' => 'mirrorless', 'price' => 1189000, 'blurb' => '24MP stills and video body, 40fps burst, superb autofocus tracking.'],
        ['name' => 'Z6 III', 'brand' => 'nikon', 'sub' => 'mirrorless', 'price' => 1095000, 'blurb' => 'Partially stacked sensor, 6K RAW internal, weather-sealed magnesium build.'],
        ['name' => 'X-T5', 'brand' => 'fujifilm', 'sub' => 'mirrorless', 'price' => 749000, 'blurb' => '40MP APS-C with classic dials and seven stops of stabilisation.'],
        ['name' => 'Lumix S5 II', 'brand' => 'panasonic', 'sub' => 'mirrorless', 'price' => 899000, 'blurb' => 'Phase-detect full-frame with unlimited 6K recording and active cooling.'],
        ['name' => 'ZV-E10 II', 'brand' => 'sony', 'sub' => 'mirrorless', 'price' => 389000, 'blurb' => 'Creator-focused APS-C vlogging body with big battery and clean 4K.'],
        ['name' => 'EOS R50', 'brand' => 'canon', 'sub' => 'mirrorless', 'price' => 285000, 'blurb' => 'Light entry mirrorless with guided interface and subject detection.'],
        ['name' => 'Z fc', 'brand' => 'nikon', 'sub' => 'mirrorless', 'price' => 325000, 'blurb' => 'Retro-styled APS-C body with tactile dials and a vari-angle screen.'],

        // Cameras — Compact.
        ['name' => 'X100VI', 'brand' => 'fujifilm', 'sub' => 'compact', 'price' => 895000, 'blurb' => 'Fixed 23mm f/2 compact with IBIS and film simulation recipes.'],
        ['name' => 'PowerShot G7 X Mark III', 'brand' => 'canon', 'sub' => 'compact', 'price' => 279000, 'blurb' => 'Pocket vlogging compact with live streaming and 4K30.'],
        ['name' => 'RX100 VII', 'brand' => 'sony', 'sub' => 'compact', 'price' => 435000, 'blurb' => 'One-inch sensor with a 24-200mm zoom and real-time tracking.'],

        // Cameras — Action & 360.
        ['name' => 'HERO13 Black', 'brand' => 'gopro', 'sub' => 'action-360', 'price' => 175000, 'blurb' => 'Waterproof action camera with swappable HB-series lens mods.'],
        ['name' => 'X4', 'brand' => 'insta360', 'sub' => 'action-360', 'price' => 165000, 'blurb' => '8K 360 capture with invisible selfie stick and reframe editing.'],
        ['name' => 'Ace Pro 2', 'brand' => 'insta360', 'sub' => 'action-360', 'price' => 149000, 'blurb' => 'Co-engineered action camera with a flip screen and 8K30 capture.'],

        // Drones — Consumer.
        ['name' => 'Mavic 4 Pro', 'brand' => 'dji', 'sub' => 'consumer-drones', 'price' => 1650000, 'blurb' => 'Three-camera gimbal, 51-minute flight, omnidirectional sensing.'],
        ['name' => 'Air 3S', 'brand' => 'dji', 'sub' => 'consumer-drones', 'price' => 725000, 'blurb' => 'Dual focal lengths, LiDAR night obstacle sensing, 45-minute flight.'],
        ['name' => 'Mini 4 Pro', 'brand' => 'dji', 'sub' => 'consumer-drones', 'price' => 465000, 'blurb' => 'Under 249g, no registration needed, full obstacle avoidance and 4K60.'],
        ['name' => 'Neo', 'brand' => 'dji', 'sub' => 'consumer-drones', 'price' => 129000, 'blurb' => '135g palm-launch drone that follows and films without a controller.'],
        ['name' => 'EVO Lite+', 'brand' => 'autel', 'sub' => 'consumer-drones', 'price' => 545000, 'blurb' => 'One-inch sensor with adjustable aperture and 40-minute endurance.'],

        // Drones — FPV.
        ['name' => 'Avata 2', 'brand' => 'dji', 'sub' => 'fpv', 'price' => 335000, 'blurb' => 'Cinewhoop FPV with propeller guards and single-stick acro mode.'],
        ['name' => 'FPV Goggles 3', 'brand' => 'dji', 'sub' => 'fpv', 'price' => 189000, 'blurb' => 'Low-latency FPV headset with real-view passthrough and dioptre dials.'],

        // Drones — Accessories.
        ['name' => 'Mavic 4 Pro Flight Battery', 'brand' => 'dji', 'sub' => 'drone-accessories', 'price' => 42900, 'blurb' => 'Spare intelligent flight battery with self-discharge protection.'],
        ['name' => 'Universal Propeller Set', 'brand' => null, 'sub' => 'drone-accessories', 'price' => 8900, 'blurb' => 'Low-noise replacement propellers with quick-release hubs.'],

        // Lenses — Prime.
        ['name' => 'RF 50mm f/1.8 STM', 'brand' => 'canon', 'sub' => 'prime-lenses', 'price' => 68000, 'blurb' => 'Compact fast prime for portraits and low light on any RF body.'],
        ['name' => 'Z 40mm f/2', 'brand' => 'nikon', 'sub' => 'prime-lenses', 'price' => 89000, 'blurb' => 'Pocketable everyday prime with smooth rendering wide open.'],
        ['name' => 'FE 85mm f/1.8', 'brand' => 'sony', 'sub' => 'prime-lenses', 'price' => 178000, 'blurb' => 'Short telephoto portrait prime with quiet, confident focus.'],
        ['name' => '56mm f/1.4 DC DN', 'brand' => 'sigma', 'sub' => 'prime-lenses', 'price' => 132000, 'blurb' => 'APS-C portrait prime with creamy separation and a metal build.'],

        // Lenses — Zoom.
        ['name' => 'FE 24-70mm f/2.8 GM II', 'brand' => 'sony', 'sub' => 'zoom-lenses', 'price' => 725000, 'blurb' => 'Lightest pro standard zoom in class with fast linear focus motors.'],
        ['name' => '18-50mm f/2.8 DC DN', 'brand' => 'sigma', 'sub' => 'zoom-lenses', 'price' => 112000, 'blurb' => 'Tiny constant-aperture APS-C zoom with close 12cm focusing.'],
        ['name' => '28-75mm f/2.8 Di III G2', 'brand' => 'tamron', 'sub' => 'zoom-lenses', 'price' => 245000, 'blurb' => 'Value full-frame zoom, weather-sealed, with a customisable focus ring.'],
        ['name' => 'RF 24-105mm f/4L IS USM', 'brand' => 'canon', 'sub' => 'zoom-lenses', 'price' => 585000, 'blurb' => 'Weather-sealed L-series walkaround zoom with five stops of IS.'],

        // Gimbals — Camera.
        ['name' => 'RS 4 Pro', 'brand' => 'dji', 'sub' => 'camera-gimbals', 'price' => 289000, 'blurb' => 'Carries 4.5kg rigs, second-generation motors, focus module ready.'],
        ['name' => 'Crane 4', 'brand' => 'zhiyun', 'sub' => 'camera-gimbals', 'price' => 175000, 'blurb' => 'Built-in fill light and sling grip for run-and-gun documentary work.'],
        ['name' => 'RS 4 Mini', 'brand' => 'dji', 'sub' => 'camera-gimbals', 'price' => 132000, 'blurb' => 'Featherweight three-axis gimbal that folds into a shoulder bag.'],

        // Gimbals — Phone.
        ['name' => 'Osmo Mobile 6', 'brand' => 'dji', 'sub' => 'phone-gimbals', 'price' => 49900, 'blurb' => 'Folding phone stabiliser with extension rod and subject tracking.'],
        ['name' => 'Smooth 5S', 'brand' => 'zhiyun', 'sub' => 'phone-gimbals', 'price' => 38900, 'blurb' => 'Phone gimbal with magnetic fill light and a physical zoom wheel.'],

        // Accessories.
        ['name' => 'Everyday Backpack 20L', 'brand' => 'peak-design', 'sub' => 'bags-cases', 'price' => 82000, 'blurb' => 'Weatherproof camera pack with FlexFold dividers and side access.'],
        ['name' => 'AD200 Pro II', 'brand' => 'godox', 'sub' => 'lighting', 'price' => 118000, 'blurb' => '200Ws pocket flash with swappable heads and TTL across systems.'],
        ['name' => 'Extreme Pro 256GB V90', 'brand' => 'sandisk', 'sub' => 'memory-storage', 'price' => 46900, 'blurb' => 'V90 SD card rated for sustained 6K and 8K internal recording.'],
        ['name' => '190go! Aluminium Tripod', 'brand' => 'manfrotto', 'sub' => 'tripods-supports', 'price' => 68000, 'blurb' => 'Twist-lock aluminium legs with a 90-degree column for macro.'],
    ];

    /**
     * Which placeholder mark suits each sub category.
     *
     * @var array<string, string>
     */
    private const KINDS = [
        'mirrorless' => 'camera',
        'compact' => 'camera',
        'action-360' => 'camera',
        'consumer-drones' => 'drone',
        'fpv' => 'drone',
        'drone-accessories' => 'accessory',
        'prime-lenses' => 'lens',
        'zoom-lenses' => 'lens',
        'camera-gimbals' => 'gimbal',
        'phone-gimbals' => 'gimbal',
        'bags-cases' => 'accessory',
        'lighting' => 'accessory',
        'memory-storage' => 'accessory',
        'tripods-supports' => 'accessory',
    ];

    /**
     * Selling points to draw each product's key features from.
     *
     * @var array<string, list<string>>
     */
    private const FEATURES = [
        'camera' => [
            'In-body image stabilisation',
            'Weather-sealed body',
            'Dual UHS-II card slots',
            '10-bit internal recording',
            'Eye and subject detection autofocus',
            'Fully articulating touchscreen',
            'USB-C power delivery while shooting',
        ],
        'lens' => [
            'Constant maximum aperture',
            'Weather-sealed mount',
            'Linear stepping focus motor',
            'Customisable control ring',
            'Rounded nine-blade diaphragm',
            'Close focusing to 0.12m',
        ],
        'drone' => [
            'Omnidirectional obstacle sensing',
            'Return to home on signal loss',
            'Vertical shooting mode',
            '4K60 HDR video',
            'Up to 45 minutes of flight',
            'ActiveTrack subject following',
        ],
        'gimbal' => [
            'Three-axis stabilisation',
            'Folding design with a locking arm',
            'Built-in touchscreen',
            'Up to 12 hours of battery',
            'Bluetooth shutter control',
            'Quick-release camera plate',
        ],
        'accessory' => [
            'Weatherproof construction',
            'Two-year local warranty',
            'Ships from Colombo',
            'Compatible across major systems',
            'Quick-release mounting',
        ],
    ];

    /**
     * Closing lines for the second paragraph of the product copy.
     *
     * @var list<string>
     */
    private const CLOSING_LINES = [
        'Stocked in Colombo and covered by a two-year local warranty.',
        'Bundled with the local warranty card and a genuine charger.',
        'Ships the same working day on orders placed before 3pm.',
        'Bought in and serviced locally, so repairs never leave the island.',
    ];

    /**
     * Seed forty products, each with generated images.
     */
    public function run(): void
    {
        $subCategories = SubCategory::query()->pluck('id', 'slug');
        $brands = Brand::query()->pluck('id', 'slug');
        $random = new Randomizer(new Mt19937(self::RANDOM_SEED));

        foreach (self::PRODUCTS as $index => $row) {
            $subCategoryId = $subCategories->get($row['sub']);

            if ($subCategoryId === null) {
                throw new RuntimeException("Missing sub category [{$row['sub']}]. Run the SubCategorySeeder first.");
            }

            if ($row['brand'] !== null && $brands->get($row['brand']) === null) {
                throw new RuntimeException("Missing brand [{$row['brand']}]. Run the BrandSeeder first.");
            }

            $kind = self::KINDS[$row['sub']];

            // Two thirds of the catalogue is on sale, between 5% and 22% off,
            // rounded to a round rupee figure the way a real shop would price.
            $discount = null;

            if ($random->getInt(1, 3) > 1) {
                $off = $random->getInt(5, 22) / 100;
                $discount = (int) (round($row['price'] * (1 - $off) / 100) * 100);
            }

            $pool = self::FEATURES[$kind];
            $features = [];

            $wanted = min(count($pool), max(1, $random->getInt(3, 4)));

            foreach ($random->pickArrayKeys($pool, $wanted) as $key) {
                $features[] = $pool[(int) $key];
            }

            $product = Product::query()->updateOrCreate(
                ['name' => $row['name'], 'sub_category_id' => $subCategoryId],
                [
                    'brand_id' => $row['brand'] === null ? null : $brands->get($row['brand']),
                    'base_price' => (string) $row['price'],
                    'discount_price' => $discount === null ? null : (string) $discount,
                    'description' => HtmlSanitizer::clean(sprintf(
                        '<p>%s</p><p>%s</p>',
                        e($row['blurb']),
                        e(self::CLOSING_LINES[$index % count(self::CLOSING_LINES)]),
                    )),
                    'key_features' => $features,
                ],
            );

            // Spread the catalogue over the last few months so that the
            // storefront's "Featured" ordering is not just the seeder's order.
            $created = now()->subDays($random->getInt(1, 150))->subHours($random->getInt(0, 23));

            $product->forceFill(['created_at' => $created, 'updated_at' => $created])->save();

            $this->seedImages($product, $row['brand'], $kind, $random->getInt(1, 4));
        }
    }

    /**
     * Attach generated images to a product that does not have any yet.
     */
    private function seedImages(Product $product, ?string $brandSlug, string $kind, int $count): void
    {
        if ($product->images()->exists()) {
            return;
        }

        $stem = Str::slug(($brandSlug ?? 'generic').' '.$product->name);

        foreach (range(0, $count - 1) as $variant) {
            $product->images()->create([
                'path' => $this->putImage(
                    "products/{$stem}-{$variant}.jpg",
                    fn (): string => PlaceholderImage::product($product->name, $kind, $variant),
                ),
                'sort_order' => $variant + 1,
            ]);
        }
    }
}
