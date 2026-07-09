<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class InventoryLookupController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function products(): JsonResponse
    {
        return response()->json([
            'data' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'stock_quantity']),
        ]);
    }

    public function suppliers(): JsonResponse
    {
        return response()->json([
            'data' => Supplier::query()
                ->where('is_active', true)
                ->orderBy('company_name')
                ->orderBy('name')
                ->get(['id', 'name', 'company_name']),
        ]);
    }

    public function warehouses(): JsonResponse
    {
        return response()->json([
            'data' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
