<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $stockValue = Product::query()
            ->selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as value')
            ->value('value');

        $warehouseStocks = Warehouse::query()
            ->where('is_active', true)
            ->withSum('productStocks as total_quantity', 'quantity')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'quantity' => (float) ($warehouse->total_quantity ?? 0),
                'records' => $warehouse->productStocks()->count(),
            ]);

        $maxWarehouseQuantity = max($warehouseStocks->max('quantity') ?? 0, 1);

        return Inertia::render('dashboard', [
            'stats' => [
                'totalProducts' => Product::query()->count(),
                'lowStock' => Product::query()
                    ->whereColumn('stock_quantity', '<=', 'minimum_stock_quantity')
                    ->where('stock_quantity', '>', 0)
                    ->count(),
                'outOfStock' => Product::query()->where('stock_quantity', '<=', 0)->count(),
                'stockValue' => (float) $stockValue,
                'totalSales' => (float) Sale::query()
                    ->where('status', 'completed')
                    ->sum('total_amount'),
                'totalPurchases' => (float) Purchase::query()
                    ->where('status', 'completed')
                    ->sum('total_amount'),
            ],
            'warehouseStocks' => $warehouseStocks
                ->map(fn (array $warehouse) => [
                    ...$warehouse,
                    'percentage' => $warehouse['quantity'] > 0
                        ? round(($warehouse['quantity'] / $maxWarehouseQuantity) * 100)
                        : 0,
                ])
                ->values(),
            'recentMovements' => StockMovement::query()
                ->with(['product:id,name,unit_type', 'warehouse:id,name'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (StockMovement $movement) => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'product' => $movement->product?->name ?? 'Unknown product',
                    'warehouse' => $movement->warehouse?->name ?? 'Unknown warehouse',
                    'quantity' => (float) $movement->quantity,
                    'unit' => $movement->product?->unit_type ?? 'pcs',
                ]),
            'alerts' => Product::query()
                ->leftJoin('product_stocks', 'products.id', '=', 'product_stocks.product_id')
                ->leftJoin('warehouses', 'product_stocks.warehouse_id', '=', 'warehouses.id')
                ->whereColumn('products.stock_quantity', '<=', 'products.minimum_stock_quantity')
                ->orderBy('products.stock_quantity')
                ->limit(8)
                ->get([
                    'products.id',
                    'products.name',
                    'products.stock_quantity',
                    'products.minimum_stock_quantity',
                    'products.unit_type',
                    DB::raw("COALESCE(warehouses.name, 'Unassigned') as warehouse_name"),
                ])
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'product' => $product->name,
                    'warehouse' => $product->warehouse_name,
                    'available' => (float) $product->stock_quantity,
                    'minimum' => (float) $product->minimum_stock_quantity,
                    'unit' => $product->unit_type,
                    'status' => ((float) $product->stock_quantity) <= 0 ? 'Out of stock' : 'Low stock',
                ]),
        ]);
    }
}
