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
        Schema::create('logistics_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->unsignedInteger('capacity_tons');
            $table->string('origin', 80);
            $table->string('destination', 80);
            $table->unsignedInteger('price');
            $table->string('image_path')->nullable();
            $table->string('status', 32)->default('available')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('logistics_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('logistics_vehicles')->cascadeOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('cargo_name', 120);
            $table->unsignedInteger('cargo_tons');
            $table->string('origin', 80);
            $table->string('destination', 80);
            $table->unsignedInteger('price');
            $table->string('status', 32)->default('booked')->index();
            $table->timestamp('booked_at')->nullable();
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
        Schema::dropIfExists('logistics_shipments');
        Schema::dropIfExists('logistics_vehicles');
    }
};
