<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = Purchase::query()
            ->with(['supplier:id,name,company_name', 'warehouse:id,name'])
            ->when($search, fn ($query) => $query->where('purchase_number', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->through(fn (Purchase $purchase) => [
                'id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'warehouse_id' => $purchase->warehouse_id,
                'purchase_number' => $purchase->purchase_number,
                'supplier_name' => $purchase->supplier?->company_name ?: $purchase->supplier?->name,
                'warehouse_name' => $purchase->warehouse?->name,
                'purchase_date' => $purchase->purchase_date?->toDateString(),
                'total_amount' => $purchase->total_amount,
                'status' => $purchase->status,
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
        Purchase::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return to_route('purchases.index')->with('success', 'Purchase created.');
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load(['supplier:id,name,company_name', 'warehouse:id,name']);

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => [
                ...$purchase->toArray(),
                'supplier_name' => $purchase->supplier?->company_name ?: $purchase->supplier?->name,
                'warehouse_name' => $purchase->warehouse?->name,
            ],
        ]);
    }

    public function edit(Purchase $purchase): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $purchase,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $purchase->update($this->validated($request, $purchase->id));

        return to_route('purchases.index')->with('success', 'Purchase updated.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $purchase->delete();

        return to_route('purchases.index')->with('success', 'Purchase deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_number' => ['required', 'string', 'max:255', Rule::unique('purchases', 'purchase_number')->ignore($ignoreId)],
            'purchase_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function options(): array
    {
        return [
            'suppliers' => Supplier::query()->orderBy('company_name')->orderBy('name')->get(['id', 'name', 'company_name']),
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
            'name' => 'Purchases',
            'singular' => 'purchase',
            'route' => 'purchases',
            'description' => 'Create and manage supplier purchase records.',
            'fields' => [
                ['name' => 'purchase_number', 'label' => 'Purchase number', 'type' => 'text', 'required' => true],
                ['name' => 'supplier_id', 'label' => 'Supplier', 'type' => 'select', 'optionsKey' => 'suppliers', 'required' => true],
                ['name' => 'warehouse_id', 'label' => 'Warehouse', 'type' => 'select', 'optionsKey' => 'warehouses', 'required' => true],
                ['name' => 'purchase_date', 'label' => 'Purchase date', 'type' => 'date', 'required' => true],
                ['name' => 'total_amount', 'label' => 'Total amount', 'type' => 'number', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'optionsKey' => 'statuses', 'required' => true],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'columns' => [
                ['key' => 'purchase_number', 'label' => 'Purchase'],
                ['key' => 'supplier_name', 'label' => 'Supplier'],
                ['key' => 'warehouse_name', 'label' => 'Warehouse'],
                ['key' => 'purchase_date', 'label' => 'Date'],
                ['key' => 'total_amount', 'label' => 'Amount'],
                ['key' => 'status', 'label' => 'Status'],
            ],
        ];
    }
}
