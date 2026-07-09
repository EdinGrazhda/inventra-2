<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = Supplier::query()
            ->withCount(['products', 'purchases'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
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
        Supplier::create($this->validated($request));

        return to_route('suppliers.index')->with('success', 'Supplier created.');
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->loadCount(['products', 'purchases']);

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => $supplier,
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $supplier,
            'options' => [],
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return to_route('suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return to_route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function module(): array
    {
        return [
            'name' => 'Suppliers',
            'singular' => 'supplier',
            'route' => 'suppliers',
            'description' => 'Manage supplier contacts, companies, and purchasing notes.',
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'company_name', 'label' => 'Company name', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                ['name' => 'address', 'label' => 'Address', 'type' => 'textarea'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'company_name', 'label' => 'Company'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'phone', 'label' => 'Phone'],
                ['key' => 'products_count', 'label' => 'Products'],
            ],
        ];
    }
}
