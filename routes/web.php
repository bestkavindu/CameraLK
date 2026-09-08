<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.store.home')->name('home');
Route::livewire('products', 'pages::store.products')->name('store.products');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('dashboard/categories', 'pages::categories.index')->name('categories.index');
    Route::livewire('dashboard/sub-categories', 'pages::sub-categories.index')->name('sub-categories.index');

    Route::livewire('dashboard/brands', 'pages::brands.index')->name('brands.index');

    Route::livewire('dashboard/products', 'pages::products.index')->name('products.index');
    Route::livewire('dashboard/products/create', 'pages::products.form')->name('products.create');
    Route::livewire('dashboard/products/{product}/edit', 'pages::products.form')->name('products.edit');
});

require __DIR__.'/settings.php';
