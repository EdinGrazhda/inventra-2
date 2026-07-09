<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\ProductStockController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Inventory\ReportController;
use App\Http\Controllers\Inventory\SaleController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\UserRoleController;
use App\Http\Controllers\Inventory\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'auth/login', [
    'canResetPassword' => Route::has('password.request'),
    'status' => fn () => session('status'),
])->middleware('guest')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('products', ProductController::class)->middleware('permission:products.access');
    Route::resource('categories', CategoryController::class)->middleware('permission:categories.access');
    Route::resource('suppliers', SupplierController::class)->middleware('permission:suppliers.access');
    Route::resource('warehouses', WarehouseController::class)->middleware('permission:warehouses.access');
    Route::resource('stock', ProductStockController::class)->parameters(['stock' => 'stock'])->middleware('permission:stock.access');
    Route::resource('stock-movements', StockMovementController::class)->parameters([
        'stock-movements' => 'stockMovement',
    ])->middleware('permission:stock_movements.access');
    Route::resource('stock-transfers', StockTransferController::class)->parameters([
        'stock-transfers' => 'stockTransfer',
    ])->middleware('permission:stock_transfers.access');
    Route::resource('purchases', PurchaseController::class)->middleware('permission:purchases.access');
    Route::resource('sales', SaleController::class)->middleware('permission:sales.access');
    Route::resource('reports', ReportController::class)->middleware('permission:reports.access');
    Route::resource('users-roles', UserRoleController::class)->parameters([
        'users-roles' => 'user',
    ])->middleware('permission:users_roles.access');
});

require __DIR__.'/settings.php';
