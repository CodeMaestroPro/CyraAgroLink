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
        Schema::table('marketplace_commodities', function (Blueprint $table) {
            $table->string('scientific_name', 150)->nullable()->after('name');
            $table->unsignedBigInteger('previous_price_per_ton')->nullable()->after('price_per_ton');
            $table->unsignedBigInteger('day_high')->nullable()->after('previous_price_per_ton');
            $table->unsignedBigInteger('day_low')->nullable()->after('day_high');
            $table->unsignedInteger('volume_tons')->nullable()->after('day_low');
            $table->unsignedInteger('open_interest_tons')->nullable()->after('volume_tons');
        });

        Schema::create('exchange_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commodity_id')->constrained('marketplace_commodities')->cascadeOnDelete();
            $table->string('side', 8); // buy|sell
            $table->unsignedInteger('quantity_tons');
            $table->unsignedBigInteger('price_per_ton');
            $table->string('status', 32)->default('open')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commodity_id', 'side', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_orders');

        Schema::table('marketplace_commodities', function (Blueprint $table) {
            $table->dropColumn([
                'scientific_name',
                'previous_price_per_ton',
                'day_high',
                'day_low',
                'volume_tons',
                'open_interest_tons',
            ]);
        });
    }
};
