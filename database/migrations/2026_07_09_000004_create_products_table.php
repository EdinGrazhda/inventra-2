<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->index();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('unit_type', 30)->default('pcs');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('stock_quantity', 15, 3)->default(0);
            $table->decimal('minimum_stock_quantity', 15, 3)->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['category_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
