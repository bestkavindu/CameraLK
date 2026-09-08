<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Guarded so the whole seeder can be re-run without tripping the
        // unique email constraint.
        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Order matters: products reference brands and sub categories, and sub
        // categories reference their main category.
        $this->call([
            CategorySeeder::class,
            SubCategorySeeder::class,
            BrandSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
