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
        Schema::create('cooperatives', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('location', 120)->nullable();
            $table->string('description', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('savings_pool_ngn')->default(0);
            $table->string('status', 24)->default('active'); // active|closed
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('cooperative_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 24)->default('member'); // admin|member
            $table->unsignedBigInteger('savings_balance_ngn')->default(0);
            $table->string('status', 24)->default('active'); // active|left
            $table->dateTime('joined_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['cooperative_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('cooperative_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 40)->unique();
            $table->unsignedBigInteger('amount_ngn');
            $table->string('purpose', 200);
            $table->string('status', 24)->default('pending'); // pending|approved|disbursed|repaid|rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('disbursed_at')->nullable();
            $table->dateTime('repaid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cooperative_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('cooperative_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('description', 500);
            $table->string('status', 24)->default('open'); // open|passed|rejected|closed
            $table->unsignedInteger('yes_count')->default(0);
            $table->unsignedInteger('no_count')->default(0);
            $table->dateTime('closes_at');
            $table->dateTime('closed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cooperative_id', 'status']);
        });

        Schema::create('cooperative_ballots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_vote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('choice', 8); // yes|no
            $table->timestamps();

            $table->unique(['cooperative_vote_id', 'user_id']);
        });

        Schema::create('cooperative_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40); // contribution|loan|equipment|profit|vote|member
            $table->string('title', 160);
            $table->string('value', 80);
            $table->string('icon', 40)->default('contribution');
            $table->nullableMorphs('reference');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cooperative_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooperative_activities');
        Schema::dropIfExists('cooperative_ballots');
        Schema::dropIfExists('cooperative_votes');
        Schema::dropIfExists('cooperative_loans');
        Schema::dropIfExists('cooperative_members');
        Schema::dropIfExists('cooperatives');
    }
};
