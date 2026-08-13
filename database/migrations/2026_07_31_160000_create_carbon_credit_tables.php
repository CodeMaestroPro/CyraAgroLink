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
        Schema::create('carbon_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance_tco2e', 12, 2)->default(0);
            $table->decimal('lifetime_earned_tco2e', 12, 2)->default(0);
            $table->unsignedSmallInteger('sustainability_score')->default(70);
            $table->timestamps();
        });

        Schema::create('carbon_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('credits_tco2e', 12, 2);
            $table->decimal('unit_price_usd', 10, 2);
            $table->string('status', 20)->default('open');
            $table->timestamp('listed_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->string('buyer_name', 120)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('carbon_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('carbon_listings')->nullOnDelete();
            $table->string('type', 16);
            $table->string('title', 120);
            $table->string('counterparty', 120)->nullable();
            $table->decimal('credits_tco2e', 12, 2);
            $table->decimal('unit_price_usd', 10, 2)->nullable();
            $table->unsignedBigInteger('value_ngn')->default(0);
            $table->string('status', 20)->default('completed');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carbon_transactions');
        Schema::dropIfExists('carbon_listings');
        Schema::dropIfExists('carbon_accounts');
    }
};
