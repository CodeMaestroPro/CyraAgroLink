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
        Schema::table('exchange_orders', function (Blueprint $table): void {
            $table->unsignedInteger('original_quantity_tons')->default(0)->after('quantity_tons');
            $table->unsignedInteger('filled_quantity_tons')->default(0)->after('original_quantity_tons');
            $table->unsignedBigInteger('reserved_amount')->default(0)->after('price_per_ton');
        });

        Schema::create('exchange_trades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commodity_id')->constrained('marketplace_commodities')->cascadeOnDelete();
            $table->foreignId('buy_order_id')->constrained('exchange_orders')->cascadeOnDelete();
            $table->foreignId('sell_order_id')->constrained('exchange_orders')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('quantity_tons');
            $table->unsignedBigInteger('price_per_ton');
            $table->unsignedBigInteger('notional_amount');
            $table->timestamp('traded_at')->useCurrent();
            $table->timestamps();

            $table->index(['commodity_id', 'traded_at']);
            $table->index(['buyer_id', 'traded_at']);
            $table->index(['seller_id', 'traded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_trades');

        Schema::table('exchange_orders', function (Blueprint $table): void {
            $table->dropColumn([
                'original_quantity_tons',
                'filled_quantity_tons',
                'reserved_amount',
            ]);
        });
    }
};
