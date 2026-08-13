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
        Schema::create('arbitrage_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('commodity_name', 120);
            $table->string('buy_market', 120);
            $table->string('sell_market', 120);
            $table->unsignedBigInteger('buy_price_per_ton');
            $table->unsignedBigInteger('sell_price_per_ton');
            $table->unsignedBigInteger('transport_cost')->default(0);
            $table->unsignedBigInteger('warehouse_cost')->default(0);
            $table->unsignedBigInteger('fees_cost')->default(0);
            $table->decimal('roi_percent', 8, 2)->default(0);
            $table->string('recommendation_title', 120)->nullable();
            $table->text('recommendation_body')->nullable();
            $table->boolean('is_best')->default(false)->index();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbitrage_opportunities');
    }
};
