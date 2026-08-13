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
        Schema::create('processing_factories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('state', 80)->nullable();
            $table->json('services');
            $table->unsignedInteger('capacity_tons_per_day')->default(20);
            $table->unsignedTinyInteger('utilization_percent')->default(60);
            $table->unsignedInteger('active_jobs')->default(0);
            $table->unsignedInteger('completed_jobs')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('processing_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('factory_id')->nullable()->constrained('processing_factories')->nullOnDelete();
            $table->string('reference', 32);
            $table->string('service', 40);
            $table->string('product', 120);
            $table->decimal('quantity_tons', 12, 2);
            $table->string('status', 32)->default('queued');
            $table->unsignedBigInteger('fee_ngn')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
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
        Schema::dropIfExists('processing_requests');
        Schema::dropIfExists('processing_factories');
    }
};
