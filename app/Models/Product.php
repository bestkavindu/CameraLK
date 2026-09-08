<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $brand_id
 * @property int $sub_category_id
 * @property string $name
 * @property string $base_price
 * @property string|null $discount_price
 * @property string|null $description
 * @property array<int, string>|null $key_features
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Brand|null $brand
 * @property-read SubCategory $subCategory
 * @property-read Collection<int, ProductImage> $images
 */
#[Fillable(['brand_id', 'sub_category_id', 'name', 'base_price', 'discount_price', 'description', 'key_features'])]
class Product extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'key_features' => 'array',
        ];
    }

    /**
     * Get the brand the product is sold under, when one is set.
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the sub category the product belongs to.
     *
     * @return BelongsTo<SubCategory, $this>
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Get the product images ordered for display.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get the main category the product belongs to, through its sub category.
     */
    public function mainCategory(): Category
    {
        return $this->subCategory->category;
    }

    /**
     * Get the price a customer actually pays.
     */
    public function effectivePrice(): string
    {
        return $this->discount_price ?? $this->base_price;
    }

    /**
     * Get the discount as a whole percentage, or null when there is no discount.
     */
    public function discountPercentage(): ?int
    {
        if ($this->discount_price === null || (float) $this->base_price <= 0.0) {
            return null;
        }

        $off = ((float) $this->base_price - (float) $this->discount_price) / (float) $this->base_price;

        return (int) round($off * 100);
    }

    /**
     * Get the image shown in listings.
     */
    public function primaryImage(): ?ProductImage
    {
        return $this->images->first();
    }
}
