<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $records = Category::query()
            ->withCount('products')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
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
        Category::create($this->validated($request));

        return to_route('categories.index')->with('success', 'Category created.');
    }

    public function show(Category $category): Response
    {
        $category->loadCount('products');

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => $category,
        ]);
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => $category,
            'options' => [],
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category->id));

        return to_route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return to_route('categories.index')->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }

    private function module(): array
    {
        return [
            'name' => 'Categories',
            'singular' => 'category',
            'route' => 'categories',
            'description' => 'Organize products into active inventory groups.',
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'products_count', 'label' => 'Products'],
                ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
            ],
        ];
    }
}
