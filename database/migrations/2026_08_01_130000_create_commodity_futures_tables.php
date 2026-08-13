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
        Schema::create('futures_contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('symbol', 32)->unique();
            $table->foreignId('commodity_id')->nullable()->constrained('marketplace_commodities')->nullOnDelete();
            $table->string('expiry_label', 40);
            $table->date('expires_on');
            $table->unsignedInteger('contract_size_tons')->default(1);
            $table->unsignedBigInteger('last_price');
            $table->unsignedBigInteger('day_high')->nullable();
            $table->unsignedBigInteger('day_low')->nullable();
            $table->unsignedInteger('volume')->default(0);
            $table->unsignedInteger('open_interest')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'expires_on']);
        });

        Schema::create('futures_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('futures_contracts')->cascadeOnDelete();
            $table->string('reference', 32);
            $table->string('side', 8); // buy|sell
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('filled_quantity')->default(0);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('margin_ngn');
            $table->string('status', 24)->default('open'); // open|filled|cancelled|partial
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reference']);
            $table->index(['contract_id', 'status', 'side']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('futures_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('futures_contracts')->cascadeOnDelete();
            $table->string('reference', 32);
            $table->string('side', 8); // long|short
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('entry_price');
            $table->unsignedBigInteger('margin_ngn');
            $table->bigInteger('realized_pnl_ngn')->nullable();
            $table->string('status', 16)->default('open'); // open|closed
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reference']);
            $table->index(['user_id', 'status']);
            $table->index(['contract_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('futures_positions');
        Schema::dropIfExists('futures_orders');
        Schema::dropIfExists('futures_contracts');
    }
};
