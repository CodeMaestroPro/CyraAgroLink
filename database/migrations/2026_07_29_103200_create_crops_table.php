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
        Schema::create('crops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('variety', 120)->nullable();
            $table->string('growth_stage', 80)->default('seedling')->index();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('health_status', 40)->default('good')->index();
            $table->string('health_notes')->nullable();
            $table->string('next_activity')->nullable();
            $table->timestamp('next_activity_at')->nullable();
            $table->timestamp('planted_at')->nullable();
            $table->timestamp('expected_harvest_at')->nullable();
            $table->text('ai_recommendation')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['farm_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};
