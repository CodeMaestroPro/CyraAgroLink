<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consumer_products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('category', 40)->index();
            $table->string('unit', 16)->default('kg');
            $table->unsignedInteger('price_per_unit');
            $table->unsignedInteger('stock_qty')->default(0);
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('consumer_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('consumer_products')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('consumer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('delivery_note', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('consumer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('consumer_orders')->cascadeOnDelete();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('consumer_products')
                ->nullOnDelete();
            $table->string('product_name', 120);
            $table->string('unit', 16)->default('kg');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumer_order_items');
        Schema::dropIfExists('consumer_orders');
        Schema::dropIfExists('consumer_cart_items');
        Schema::dropIfExists('consumer_products');
    }
};
