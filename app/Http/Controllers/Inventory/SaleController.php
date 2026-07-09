<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $products = Product::query()
            ->when($search, fn ($query) => $query->where(fn ($products) => $products
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(12, ['id', 'name', 'sku', 'barcode', 'unit_type', 'selling_price', 'stock_quantity', 'minimum_stock_quantity', 'is_active'])
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'unit_type' => $product->unit_type,
                'selling_price' => $product->selling_price,
                'stock_quantity' => $product->stock_quantity,
                'minimum_stock_quantity' => $product->minimum_stock_quantity,
                'is_active' => $product->is_active,
                'status' => $product->isOutOfStock()
                    ? 'out'
                    : ($product->isLowStock() ? 'low' : 'available'),
            ])
            ->withQueryString();

        $records = Sale::query()
            ->with(['warehouse:id,name', 'items.product:id,name,sku'])
            ->when($search, fn ($query) => $query->where('sale_number', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%"))
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'customer_name' => $sale->customer_name,
                'customer_phone' => $sale->customer_phone,
                'warehouse_id' => $sale->warehouse_id,
                'warehouse_name' => $sale->warehouse?->name,
                'sale_date' => $sale->sale_date?->toDateString(),
                'total_amount' => $sale->total_amount,
                'status' => $sale->status,
                'items' => $sale->items->map(fn (SaleItem $item) => [
                    'id' => $item->id,
                    'product_name' => $item->product?->name,
                    'product_sku' => $item->product?->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]),
            ]);

        return Inertia::render('inventory/sales/index', [
            'products' => $products,
            'recentSales' => $records,
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
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
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $items = collect($validated['items'])
                ->map(fn (array $item) => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                ]);
            $productIds = $items->pluck('product_id');

            $products = Product::query()
                ->whereKey($productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $warehouseStocks = ProductStock::query()
                ->whereIn('product_id', $productIds)
                ->where('warehouse_id', $validated['warehouse_id'])
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($items as $index => $item) {
                /** @var Product|null $product */
                $product = $products->get($item['product_id']);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'This product is inactive and cannot be sold.',
                    ]);
                }

                if ((float) $product->stock_quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Not enough stock available for {$product->name}.",
                    ]);
                }

                $warehouseStock = $warehouseStocks->get($product->id);

                if ($warehouseStock && (float) $warehouseStock->quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'warehouse_id' => "Not enough {$product->name} stock in the selected warehouse.",
                    ]);
                }
            }

            $totalAmount = $items->sum(function (array $item) use ($products): float {
                /** @var Product $product */
                $product = $products->get($item['product_id']);

                return $item['quantity'] * (float) $product->selling_price;
            });

            $sale = Sale::create([
                'sale_number' => $this->nextSaleNumber(),
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'sale_date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($items as $item) {
                /** @var Product $product */
                $product = $products->get($item['product_id']);
                $warehouseStock = $warehouseStocks->get($product->id);
                $unitPrice = (float) $product->selling_price;
                $subtotal = $item['quantity'] * $unitPrice;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock_quantity', $item['quantity']);
                $warehouseStock?->decrement('quantity', $item['quantity']);

                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'type' => 'stock_out',
                    'quantity' => $item['quantity'],
                    'reason' => 'Sale '.$sale->sale_number,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return to_route('sales.index')->with('success', 'Sale recorded and stock decremented.');
    }

    public function show(Sale $sale): Response
    {
        $sale->load('warehouse:id,name');

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => [
                ...$sale->toArray(),
                'warehouse_name' => $sale->warehouse?->name,
            ],
        ]);
    }

    public function edit(Sale $sale): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $sale,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $sale->update($this->validated($request, $sale->id));

        return to_route('sales.index')->with('success', 'Sale updated.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $sale->delete();

        return to_route('sales.index')->with('success', 'Sale deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'sale_number' => ['required', 'string', 'max:255', Rule::unique('sales', 'sale_number')->ignore($ignoreId)],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'sale_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function options(): array
    {
        return [
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ];
    }

    private function module(): array
    {
        return [
            'name' => 'Sales',
            'singular' => 'sale',
            'route' => 'sales',
            'description' => 'Create and manage customer sales records.',
            'fields' => [
                ['name' => 'sale_number', 'label' => 'Sale number', 'type' => 'text', 'required' => true],
                ['name' => 'customer_name', 'label' => 'Customer name', 'type' => 'text'],
                ['name' => 'customer_phone', 'label' => 'Customer phone', 'type' => 'text'],
                ['name' => 'warehouse_id', 'label' => 'Warehouse', 'type' => 'select', 'optionsKey' => 'warehouses', 'required' => true],
                ['name' => 'sale_date', 'label' => 'Sale date', 'type' => 'date', 'required' => true],
                ['name' => 'total_amount', 'label' => 'Total amount', 'type' => 'number', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'optionsKey' => 'statuses', 'required' => true],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'columns' => [
                ['key' => 'sale_number', 'label' => 'Sale'],
                ['key' => 'customer_name', 'label' => 'Customer'],
                ['key' => 'warehouse_name', 'label' => 'Warehouse'],
                ['key' => 'sale_date', 'label' => 'Date'],
                ['key' => 'total_amount', 'label' => 'Amount'],
                ['key' => 'status', 'label' => 'Status'],
            ],
        ];
    }

    private function nextSaleNumber(): string
    {
        return 'SALE-'.now()->format('Ymd-His').'-'.random_int(100, 999);
    }
}
