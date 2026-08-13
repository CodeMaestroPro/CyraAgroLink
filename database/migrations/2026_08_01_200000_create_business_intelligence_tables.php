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
        Schema::create('bi_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 16)->default('6m');
            $table->unsignedBigInteger('revenue_ngn')->default(0);
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('transactions_count')->default(0);
            $table->unsignedInteger('farms_count')->default(0);
            $table->json('kpis');
            $table->json('revenue_trend');
            $table->json('commodities');
            $table->json('meta')->nullable();
            $table->dateTime('calculated_at');
            $table->timestamps();

            $table->index(['period', 'calculated_at']);
        });

        Schema::create('bi_insights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bi_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('insight_key', 80);
            $table->string('category', 40); // revenue|farms|commodities|risk|ops
            $table->string('severity', 16)->default('medium'); // low|medium|high
            $table->string('title', 160);
            $table->string('detail', 500);
            $table->string('status', 24)->default('open'); // open|acknowledged|pinned|dismissed
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('pinned_at')->nullable();
            $table->dateTime('dismissed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'insight_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_insights');
        Schema::dropIfExists('bi_snapshots');
    }
};
