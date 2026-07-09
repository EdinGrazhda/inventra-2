<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryReport;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = InventoryReport::query()
            ->with(['category:id,name', 'supplier:id,name,company_name', 'warehouse:id,name'])
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('type', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->through(fn (InventoryReport $report) => [
                'id' => $report->id,
                'name' => $report->name,
                'type' => $report->type,
                'date_from' => $report->date_from?->toDateString(),
                'date_to' => $report->date_to?->toDateString(),
                'category_id' => $report->category_id,
                'supplier_id' => $report->supplier_id,
                'warehouse_id' => $report->warehouse_id,
                'category_name' => $report->category?->name,
                'supplier_name' => $report->supplier?->company_name ?: $report->supplier?->name,
                'warehouse_name' => $report->warehouse?->name,
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
        InventoryReport::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return to_route('reports.index')->with('success', 'Report saved.');
    }

    public function show(InventoryReport $report): Response
    {
        $report->load(['category:id,name', 'supplier:id,name,company_name', 'warehouse:id,name']);

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => [
                ...$report->toArray(),
                'category_name' => $report->category?->name,
                'supplier_name' => $report->supplier?->company_name ?: $report->supplier?->name,
                'warehouse_name' => $report->warehouse?->name,
            ],
        ]);
    }

    public function edit(InventoryReport $report): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $report,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, InventoryReport $report): RedirectResponse
    {
        $report->update($this->validated($request));

        return to_route('reports.index')->with('success', 'Report updated.');
    }

    public function destroy(InventoryReport $report): RedirectResponse
    {
        $report->delete();

        return to_route('reports.index')->with('success', 'Report deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['products', 'low_stock', 'out_of_stock', 'sales', 'purchases', 'stock_movements', 'warehouse_stock', 'supplier_purchases'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function options(): array
    {
        return [
            'reportTypes' => [
                ['value' => 'products', 'label' => 'Products report'],
                ['value' => 'low_stock', 'label' => 'Low stock report'],
                ['value' => 'out_of_stock', 'label' => 'Out of stock report'],
                ['value' => 'sales', 'label' => 'Sales report'],
                ['value' => 'purchases', 'label' => 'Purchases report'],
                ['value' => 'stock_movements', 'label' => 'Stock movement report'],
                ['value' => 'warehouse_stock', 'label' => 'Warehouse stock report'],
                ['value' => 'supplier_purchases', 'label' => 'Supplier purchase report'],
            ],
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('company_name')->orderBy('name')->get(['id', 'name', 'company_name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function module(): array
    {
        return [
            'name' => 'Reports',
            'singular' => 'report',
            'route' => 'reports',
            'description' => 'Create saved report definitions with filters for later export and review.',
            'fields' => [
                ['name' => 'name', 'label' => 'Report name', 'type' => 'text', 'required' => true],
                ['name' => 'type', 'label' => 'Report type', 'type' => 'select', 'optionsKey' => 'reportTypes', 'required' => true],
                ['name' => 'date_from', 'label' => 'Date from', 'type' => 'date'],
                ['name' => 'date_to', 'label' => 'Date to', 'type' => 'date'],
                ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'optionsKey' => 'categories'],
                ['name' => 'supplier_id', 'label' => 'Supplier', 'type' => 'select', 'optionsKey' => 'suppliers'],
                ['name' => 'warehouse_id', 'label' => 'Warehouse', 'type' => 'select', 'optionsKey' => 'warehouses'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Report'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'date_from', 'label' => 'From'],
                ['key' => 'date_to', 'label' => 'To'],
                ['key' => 'warehouse_name', 'label' => 'Warehouse'],
            ],
        ];
    }
}
