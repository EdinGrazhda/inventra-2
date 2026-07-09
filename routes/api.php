<?php

use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\InventoryLookupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('user', AuthenticatedUserController::class)->name('api.user');

    Route::prefix('inventory/lookups')->name('api.inventory.lookups.')->group(function () {
        Route::get('categories', [InventoryLookupController::class, 'categories'])
            ->middleware('permission:categories.access')
            ->name('categories');
        Route::get('products', [InventoryLookupController::class, 'products'])
            ->middleware('permission:products.access')
            ->name('products');
        Route::get('suppliers', [InventoryLookupController::class, 'suppliers'])
            ->middleware('permission:suppliers.access')
            ->name('suppliers');
        Route::get('warehouses', [InventoryLookupController::class, 'warehouses'])
            ->middleware('permission:warehouses.access')
            ->name('warehouses');
    });
});
