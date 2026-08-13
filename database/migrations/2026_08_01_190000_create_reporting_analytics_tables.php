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
        Schema::create('analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 16)->default('6m'); // 3m|6m|12m|ytd
            $table->unsignedBigInteger('revenue_ngn')->default(0);
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('transactions_count')->default(0);
            $table->unsignedInteger('farms_count')->default(0);
            $table->json('revenue_trend');
            $table->json('transactions_trend');
            $table->json('segments');
            $table->json('regions');
            $table->json('operations')->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('calculated_at');
            $table->timestamps();

            $table->index(['period', 'calculated_at']);
        });

        Schema::create('custom_report_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('report_type', 40); // financial|operations|segment|regional|custom
            $table->string('period', 16)->default('6m');
            $table->string('segment', 40)->nullable();
            $table->string('notes', 500)->nullable();
            $table->string('status', 24)->default('queued'); // queued|ready|downloaded|cancelled
            $table->string('file_name', 120)->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('downloaded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_report_requests');
        Schema::dropIfExists('analytics_snapshots');
    }
};
