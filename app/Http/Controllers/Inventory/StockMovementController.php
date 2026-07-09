<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = StockMovement::query()
            ->with(['product:id,name,sku', 'warehouse:id,name', 'creator:id,name'])
            ->when($search, fn ($query) => $query
                ->where('type', 'like', "%{$search}%")
                ->orWhere('reason', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                ->orWhereHas('warehouse', fn ($warehouse) => $warehouse->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(10)
            ->through(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'product_id' => $movement->product_id,
                'warehouse_id' => $movement->warehouse_id,
                'product_name' => $movement->product?->name,
                'warehouse_name' => $movement->warehouse?->name,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'reason' => $movement->reason,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'creator_name' => $movement->creator?->name,
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
        StockMovement::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return to_route('stock-movements.index')->with('success', 'Stock movement created.');
    }

    public function show(StockMovement $stockMovement): Response
    {
        $stockMovement->load(['product:id,name,sku', 'warehouse:id,name', 'creator:id,name']);

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => [
                ...$stockMovement->toArray(),
                'product_name' => $stockMovement->product?->name,
                'warehouse_name' => $stockMovement->warehouse?->name,
                'creator_name' => $stockMovement->creator?->name,
            ],
        ]);
    }

    public function edit(StockMovement $stockMovement): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $stockMovement,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, StockMovement $stockMovement): RedirectResponse
    {
        $stockMovement->update($this->validated($request));

        return to_route('stock-movements.index')->with('success', 'Stock movement updated.');
    }

    public function destroy(StockMovement $stockMovement): RedirectResponse
    {
        $stockMovement->delete();

        return to_route('stock-movements.index')->with('success', 'Stock movement deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'type' => ['required', Rule::in(['stock_in', 'stock_out', 'adjustment', 'transfer'])],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string', 'max:255'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function options(): array
    {
        return [
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'movementTypes' => [
                ['value' => 'stock_in', 'label' => 'Stock in'],
                ['value' => 'stock_out', 'label' => 'Stock out'],
                ['value' => 'adjustment', 'label' => 'Adjustment'],
                ['value' => 'transfer', 'label' => 'Transfer'],
            ],
        ];
    }

    private function module(): array
    {
        return [
            'name' => 'Stock Movements',
            'singular' => 'stock movement',
            'route' => 'stock-movements',
            'description' => 'Audit and manage stock in, stock out, adjustments, and transfers.',
            'fields' => [
                ['name' => 'product_id', 'label' => 'Product', 'type' => 'select', 'optionsKey' => 'products', 'required' => true],
                ['name' => 'warehouse_id', 'label' => 'Warehouse', 'type' => 'select', 'optionsKey' => 'warehouses', 'required' => true],
                ['name' => 'type', 'label' => 'Movement type', 'type' => 'select', 'optionsKey' => 'movementTypes', 'required' => true],
                ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'required' => true],
                ['name' => 'reason', 'label' => 'Reason', 'type' => 'text'],
                ['name' => 'reference_type', 'label' => 'Reference type', 'type' => 'text'],
                ['name' => 'reference_id', 'label' => 'Reference ID', 'type' => 'number'],
            ],
            'columns' => [
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'product_name', 'label' => 'Product'],
                ['key' => 'warehouse_name', 'label' => 'Warehouse'],
                ['key' => 'quantity', 'label' => 'Quantity'],
                ['key' => 'reason', 'label' => 'Reason'],
                ['key' => 'creator_name', 'label' => 'Created by'],
            ],
        ];
    }
}
