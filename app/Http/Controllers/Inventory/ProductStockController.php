<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductStockController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = ProductStock::query()
            ->with(['product:id,name,sku,minimum_stock_quantity', 'warehouse:id,name'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('warehouse', fn ($warehouse) => $warehouse->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(10)
            ->through(fn (ProductStock $stock) => [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'product_name' => $stock->product?->name,
                'product_sku' => $stock->product?->sku,
                'warehouse_name' => $stock->warehouse?->name,
                'quantity' => $stock->quantity,
                'minimum_stock_quantity' => $stock->product?->minimum_stock_quantity,
            ])
            ->withQueryString();

        return Inertia::render('inventory/crud/index', [
            'module' => $this->module(),
            'records' => $records,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => null,
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProductStock::create($this->validated($request));

        return to_route('stock.index')->with('success', 'Stock record created.');
    }

    public function show(ProductStock $stock): Response
    {
        $stock->load(['product:id,name,sku,minimum_stock_quantity', 'warehouse:id,name']);

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => [
                ...$stock->toArray(),
                'product_name' => $stock->product?->name,
                'product_sku' => $stock->product?->sku,
                'warehouse_name' => $stock->warehouse?->name,
                'minimum_stock_quantity' => $stock->product?->minimum_stock_quantity,
            ],
        ]);
    }

    public function edit(ProductStock $stock): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $stock,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, ProductStock $stock): RedirectResponse
    {
        $stock->update($this->validated($request, $stock->id));

        return to_route('stock.index')->with('success', 'Stock record updated.');
    }

    public function destroy(ProductStock $stock): RedirectResponse
    {
        $stock->delete();

        return to_route('stock.index')->with('success', 'Stock record deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('product_stocks', 'product_id')
                    ->where('warehouse_id', $request->input('warehouse_id'))
                    ->ignore($ignoreId),
            ],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function options(): array
    {
        return [
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function module(): array
    {
        return [
            'name' => 'Product Stock',
            'singular' => 'stock record',
            'route' => 'stock',
            'description' => 'Track product quantity by warehouse.',
            'fields' => [
                ['name' => 'product_id', 'label' => 'Product', 'type' => 'select', 'optionsKey' => 'products', 'required' => true],
                ['name' => 'warehouse_id', 'label' => 'Warehouse', 'type' => 'select', 'optionsKey' => 'warehouses', 'required' => true],
                ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'required' => true],
            ],
            'columns' => [
                ['key' => 'product_name', 'label' => 'Product'],
                ['key' => 'product_sku', 'label' => 'SKU'],
                ['key' => 'warehouse_name', 'label' => 'Warehouse'],
                ['key' => 'quantity', 'label' => 'Quantity'],
                ['key' => 'minimum_stock_quantity', 'label' => 'Minimum'],
            ],
        ];
    }
}
