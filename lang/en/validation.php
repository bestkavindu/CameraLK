<?php

/*
 * Only the pieces that differ from the framework defaults. Laravel merges this
 * over vendor/laravel/framework/.../lang/en/validation.php, so the standard
 * messages still apply.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Livewire binds forms to camelCase properties, which read badly in error
    | messages ("The subCategoryId field is required"). These names are also
    | what Livewire uses when an upload fails at the PHP level, where the
    | request never reaches our own validation rules.
    |
    */

    'attributes' => [
        // Livewire posts temporary uploads to its endpoint as `files.*`...
        'files' => 'image',
        'files.*' => 'image',

        'image' => 'image',
        'newImages' => 'images',
        'newImages.*' => 'image',

        'brandId' => 'brand',
        'categoryId' => 'category',
        'subCategoryId' => 'sub category',
        'basePrice' => 'base price',
        'discountPrice' => 'discount price',
        'keyFeatures' => 'key features',
        'keyFeatures.*' => 'key feature',
    ],

];
