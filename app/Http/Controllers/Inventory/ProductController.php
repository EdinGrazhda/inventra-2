<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = Product::query()
            ->with(['category:id,name', 'supplier:id,name,company_name'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'category_id' => $product->category_id,
                'supplier_id' => $product->supplier_id,
                'category_name' => $product->category?->name,
                'supplier_name' => $product->supplier?->company_name ?: $product->supplier?->name,
                'unit_type' => $product->unit_type,
                'purchase_price' => $product->purchase_price,
                'selling_price' => $product->selling_price,
                'stock_quantity' => $product->stock_quantity,
                'minimum_stock_quantity' => $product->minimum_stock_quantity,
                'is_active' => $product->is_active,
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
        Product::create($this->validated($request));

        return to_route('products.index')->with('success', 'Product created.');
    }

    public function show(Product $product): Response
    {
        $product->load(['category:id,name', 'supplier:id,name,company_name']);

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => [
                ...$product->toArray(),
                'category_name' => $product->category?->name,
                'supplier_name' => $product->supplier?->company_name ?: $product->supplier?->name,
            ],
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $product,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product->id));

        return to_route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('products.index')->with('success', 'Product deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($ignoreId)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
            'unit_type' => ['required', 'string', 'max:30'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'numeric', 'min:0'],
            'minimum_stock_quantity' => ['required', 'numeric', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }

    private function options(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('company_name')->orderBy('name')->get(['id', 'name', 'company_name']),
        ];
    }

    private function module(): array
    {
        return [
            'name' => 'Products',
            'singular' => 'product',
            'route' => 'products',
            'description' => 'Manage product catalog, SKU codes, prices, thresholds, and status.',
            'fields' => [
                ['name' => 'name', 'label' => 'Product name', 'type' => 'text', 'required' => true],
                ['name' => 'sku', 'label' => 'SKU/code', 'type' => 'text', 'required' => true],
                ['name' => 'barcode', 'label' => 'Barcode', 'type' => 'text'],
                ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'optionsKey' => 'categories', 'required' => true],
                ['name' => 'supplier_id', 'label' => 'Supplier', 'type' => 'select', 'optionsKey' => 'suppliers'],
                ['name' => 'unit_type', 'label' => 'Unit type', 'type' => 'text', 'required' => true],
                ['name' => 'purchase_price', 'label' => 'Purchase price', 'type' => 'number', 'required' => true],
                ['name' => 'selling_price', 'label' => 'Selling price', 'type' => 'number', 'required' => true],
                ['name' => 'stock_quantity', 'label' => 'Stock quantity', 'type' => 'number', 'required' => true],
                ['name' => 'minimum_stock_quantity', 'label' => 'Minimum stock alert', 'type' => 'number', 'required' => true],
                ['name' => 'image_path', 'label' => 'Image path', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Product'],
                ['key' => 'sku', 'label' => 'SKU'],
                ['key' => 'category_name', 'label' => 'Category'],
                ['key' => 'supplier_name', 'label' => 'Supplier'],
                ['key' => 'stock_quantity', 'label' => 'Stock'],
                ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
            ],
        ];
    }
}
