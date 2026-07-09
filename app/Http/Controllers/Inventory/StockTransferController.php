<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = StockTransfer::query()
            ->with(['product:id,name,sku', 'fromWarehouse:id,name', 'toWarehouse:id,name'])
            ->when($search, fn ($query) => $query->whereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->latest()
            ->paginate(10)
            ->through(fn (StockTransfer $transfer) => [
                'id' => $transfer->id,
                'product_id' => $transfer->product_id,
                'from_warehouse_id' => $transfer->from_warehouse_id,
                'to_warehouse_id' => $transfer->to_warehouse_id,
                'product_name' => $transfer->product?->name,
                'from_warehouse_name' => $transfer->fromWarehouse?->name,
                'to_warehouse_name' => $transfer->toWarehouse?->name,
                'quantity' => $transfer->quantity,
                'transfer_date' => $transfer->transfer_date?->toDateString(),
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
        StockTransfer::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return to_route('stock-transfers.index')->with('success', 'Stock transfer created.');
    }

    public function show(StockTransfer $stockTransfer): Response
    {
        $stockTransfer->load(['product:id,name,sku', 'fromWarehouse:id,name', 'toWarehouse:id,name']);

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => [
                ...$stockTransfer->toArray(),
                'product_name' => $stockTransfer->product?->name,
                'from_warehouse_name' => $stockTransfer->fromWarehouse?->name,
                'to_warehouse_name' => $stockTransfer->toWarehouse?->name,
            ],
        ]);
    }

    public function edit(StockTransfer $stockTransfer): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $stockTransfer,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $stockTransfer->update($this->validated($request));

        return to_route('stock-transfers.index')->with('success', 'Stock transfer updated.');
    }

    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        $stockTransfer->delete();

        return to_route('stock-transfers.index')->with('success', 'Stock transfer deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
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
            'name' => 'Stock Transfers',
            'singular' => 'stock transfer',
            'route' => 'stock-transfers',
            'description' => 'Move stock between warehouses and keep a transfer record.',
            'fields' => [
                ['name' => 'product_id', 'label' => 'Product', 'type' => 'select', 'optionsKey' => 'products', 'required' => true],
                ['name' => 'from_warehouse_id', 'label' => 'From warehouse', 'type' => 'select', 'optionsKey' => 'warehouses', 'required' => true],
                ['name' => 'to_warehouse_id', 'label' => 'To warehouse', 'type' => 'select', 'optionsKey' => 'warehouses', 'required' => true],
                ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'required' => true],
                ['name' => 'transfer_date', 'label' => 'Transfer date', 'type' => 'date', 'required' => true],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'columns' => [
                ['key' => 'product_name', 'label' => 'Product'],
                ['key' => 'from_warehouse_name', 'label' => 'From'],
                ['key' => 'to_warehouse_name', 'label' => 'To'],
                ['key' => 'quantity', 'label' => 'Quantity'],
                ['key' => 'transfer_date', 'label' => 'Date'],
            ],
        ];
    }
}
