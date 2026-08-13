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
        Schema::create('smart_city_hubs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('kind', 24)->default('waypoint');
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('smart_city_fleet_units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('status', 32)->default('available')->index();
            $table->foreignId('hub_id')
                ->nullable()
                ->constrained('smart_city_hubs')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('smart_city_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_hub_id')->constrained('smart_city_hubs')->cascadeOnDelete();
            $table->foreignId('destination_hub_id')->constrained('smart_city_hubs')->cascadeOnDelete();
            $table->foreignId('fleet_unit_id')
                ->nullable()
                ->constrained('smart_city_fleet_units')
                ->nullOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('cargo_name', 120);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 32)->default('scheduled')->index();
            $table->unsignedInteger('route_order')->nullable()->index();
            $table->date('delivery_date')->index();
            $table->boolean('on_time')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_city_deliveries');
        Schema::dropIfExists('smart_city_fleet_units');
        Schema::dropIfExists('smart_city_hubs');
    }
};
