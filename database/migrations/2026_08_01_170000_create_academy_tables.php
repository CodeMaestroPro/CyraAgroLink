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
        Schema::create('academy_courses', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('level', 40); // Beginner|Intermediate|Advanced
            $table->string('level_tone', 24)->default('muted'); // muted|green
            $table->decimal('rating', 2, 1)->default(4.5);
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('image_path', 255);
            $table->string('summary', 400)->nullable();
            $table->json('enterprise_tags')->nullable();
            $table->unsignedTinyInteger('modules_count')->default(4);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_featured', 'sort_order']);
        });

        Schema::create('academy_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academy_course_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('progress_pct')->default(0);
            $table->string('status', 24)->default('enrolled'); // enrolled|in_progress|completed
            $table->dateTime('started_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('certificate_code', 40)->nullable()->unique();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'academy_course_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_enrollments');
        Schema::dropIfExists('academy_courses');
    }
};
