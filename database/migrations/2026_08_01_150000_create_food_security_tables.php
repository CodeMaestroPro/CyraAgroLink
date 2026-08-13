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
        Schema::create('food_security_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('index_score');
            $table->string('index_status', 24);
            $table->unsignedBigInteger('production_tons');
            $table->unsignedTinyInteger('import_dependency_pct');
            $table->unsignedBigInteger('reserves_tons');
            $table->json('commodities');
            $table->json('hunger_zones');
            $table->json('factors')->nullable();
            $table->dateTime('calculated_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('calculated_at');
        });

        Schema::create('food_security_interventions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state', 80);
            $table->string('title', 160);
            $table->string('action_type', 40); // reserve_release|subsidy_push|logistics_aid|market_support|scouting|other
            $table->string('status', 24)->default('planned'); // planned|in_progress|done|cancelled
            $table->string('notes', 500)->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['state', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_security_interventions');
        Schema::dropIfExists('food_security_snapshots');
    }
};
