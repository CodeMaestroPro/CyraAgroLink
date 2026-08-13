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
        Schema::create('equipment_listings', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('category', 80);
            $table->string('listing_type', 16)->default('sale'); // sale|rent|parts
            $table->unsignedInteger('price_usd');
            $table->string('location', 120);
            $table->decimal('rating', 2, 1)->default(4.5);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('stock')->default(1);
            $table->boolean('is_active')->default(true);
            $table->string('description', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['listing_type', 'is_active']);
            $table->index(['category', 'is_active']);
        });

        Schema::create('equipment_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('equipment_listings')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'listing_id']);
        });

        Schema::create('equipment_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('equipment_listings')->cascadeOnDelete();
            $table->string('reference', 32);
            $table->string('order_type', 16);
            $table->unsignedBigInteger('amount_ngn');
            $table->string('status', 24)->default('paid');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reference']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_orders');
        Schema::dropIfExists('equipment_favorites');
        Schema::dropIfExists('equipment_listings');
    }
};
