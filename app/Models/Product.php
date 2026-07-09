<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'barcode',
        'description',
        'unit_type',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'minimum_stock_quantity',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_quantity' => 'decimal:3',
            'minimum_stock_quantity' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->minimum_stock_quantity;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_quantity <= 0;
    }
}
