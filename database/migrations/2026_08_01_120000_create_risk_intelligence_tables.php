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
        Schema::create('risk_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_score');
            $table->string('status', 16); // low|medium|high
            $table->json('categories');
            $table->json('factors')->nullable();
            $table->dateTime('calculated_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'calculated_at']);
        });

        Schema::create('risk_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained('risk_assessments')->nullOnDelete();
            $table->string('alert_key', 80);
            $table->string('category', 40);
            $table->string('severity', 16); // low|medium|high
            $table->string('title', 160);
            $table->string('detail', 500)->nullable();
            $table->string('status', 24)->default('open'); // open|acknowledged|dismissed
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('dismissed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'alert_key']);
        });

        Schema::create('risk_mitigations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alert_id')->nullable()->constrained('risk_alerts')->nullOnDelete();
            $table->string('title', 160);
            $table->string('action_type', 40); // insure|logistics_review|market_hedge|crop_scouting|wallet_topup|other
            $table->string('status', 24)->default('planned'); // planned|in_progress|done|cancelled
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
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
        Schema::dropIfExists('risk_mitigations');
        Schema::dropIfExists('risk_alerts');
        Schema::dropIfExists('risk_assessments');
    }
};
