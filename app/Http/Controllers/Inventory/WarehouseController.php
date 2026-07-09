<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = Warehouse::query()
            ->withCount('productStocks')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
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
            'options' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Warehouse::create($this->validated($request));

        return to_route('warehouses.index')->with('success', 'Warehouse created.');
    }

    public function show(Warehouse $warehouse): Response
    {
        $warehouse->loadCount('productStocks');

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => $warehouse,
        ]);
    }

    public function edit(Warehouse $warehouse): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $warehouse,
            'options' => [],
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($this->validated($request, $warehouse->id));

        return to_route('warehouses.index')->with('success', 'Warehouse updated.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();

        return to_route('warehouses.index')->with('success', 'Warehouse deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('warehouses', 'name')->ignore($ignoreId)],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }

    private function module(): array
    {
        return [
            'name' => 'Warehouses',
            'singular' => 'warehouse',
            'route' => 'warehouses',
            'description' => 'Manage inventory locations and warehouse status.',
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'location', 'label' => 'Location'],
                ['key' => 'product_stocks_count', 'label' => 'Stock records'],
                ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
            ],
        ];
    }
}
