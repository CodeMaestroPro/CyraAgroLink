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
        Schema::create('insurance_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 160)->unique();
            $table->string('category', 40); // crop|weather|livestock|poultry|aquaculture|equipment
            $table->unsignedBigInteger('premium_ngn');
            $table->unsignedBigInteger('coverage_ngn');
            $table->unsignedSmallInteger('duration_days')->default(365);
            $table->string('description', 255)->nullable();
            $table->json('enterprise_tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('insurance_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('insurance_plans')->cascadeOnDelete();
            $table->string('reference', 32);
            $table->string('status', 24)->default('active'); // active|expired|cancelled
            $table->unsignedBigInteger('premium_ngn');
            $table->unsignedBigInteger('coverage_ngn');
            $table->dateTime('starts_at');
            $table->dateTime('expires_at');
            $table->json('covered_enterprises')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reference']);
            $table->index(['user_id', 'status']);
            $table->index(['farm_id', 'status']);
        });

        Schema::create('insurance_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('insurance_policies')->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 32);
            $table->string('title', 160);
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('amount_requested_ngn');
            $table->unsignedBigInteger('amount_paid_ngn')->nullable();
            $table->string('status', 24)->default('submitted'); // submitted|under_review|approved|rejected|paid
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reference']);
            $table->index(['user_id', 'status']);
            $table->index(['policy_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_policies');
        Schema::dropIfExists('insurance_plans');
    }
};
