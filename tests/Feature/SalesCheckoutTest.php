<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('recording a sale creates sale items and decrements stock', function () {
    AccessControl::seedDefaults();

    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->firstOrFail());

    $category = Category::create([
        'name' => 'Electronics',
        'description' => null,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'name' => 'Main Warehouse',
        'code' => 'MAIN',
        'address' => null,
        'manager_name' => null,
        'phone' => null,
        'is_active' => true,
    ]);

    $scanner = Product::create([
        'category_id' => $category->id,
        'supplier_id' => null,
        'name' => 'Barcode Scanner',
        'sku' => 'SCAN-001',
        'barcode' => null,
        'description' => null,
        'unit_type' => 'pcs',
        'purchase_price' => 20,
        'selling_price' => 45,
        'stock_quantity' => 5,
        'minimum_stock_quantity' => 1,
        'image_path' => null,
        'is_active' => true,
    ]);

    $printer = Product::create([
        'category_id' => $category->id,
        'supplier_id' => null,
        'name' => 'Receipt Printer',
        'sku' => 'PRINT-001',
        'barcode' => null,
        'description' => null,
        'unit_type' => 'pcs',
        'purchase_price' => 30,
        'selling_price' => 60,
        'stock_quantity' => 8,
        'minimum_stock_quantity' => 1,
        'image_path' => null,
        'is_active' => true,
    ]);

    ProductStock::create([
        'product_id' => $scanner->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 5,
    ]);

    ProductStock::create([
        'product_id' => $printer->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 8,
    ]);

    $this->actingAs($user)
        ->post(route('sales.store'), [
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $scanner->id, 'quantity' => 1],
                ['product_id' => $printer->id, 'quantity' => 2],
            ],
            'customer_name' => 'Walk-in',
            'customer_phone' => null,
            'notes' => null,
        ])
        ->assertRedirect(route('sales.index'));

    expect($scanner->fresh()->stock_quantity)->toBe('4.000')
        ->and($printer->fresh()->stock_quantity)->toBe('6.000')
        ->and(ProductStock::where('product_id', $scanner->id)->first()->quantity)->toBe('4.000')
        ->and(ProductStock::where('product_id', $printer->id)->first()->quantity)->toBe('6.000');

    $this->assertDatabaseHas('sale_items', [
        'product_id' => $scanner->id,
        'quantity' => 1,
        'unit_price' => 45,
        'subtotal' => 45,
    ]);

    $this->assertDatabaseHas('sale_items', [
        'product_id' => $printer->id,
        'quantity' => 2,
        'unit_price' => 60,
        'subtotal' => 120,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $scanner->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'stock_out',
        'quantity' => 1,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $printer->id,
        'warehouse_id' => $warehouse->id,
        'type' => 'stock_out',
        'quantity' => 2,
    ]);
});
