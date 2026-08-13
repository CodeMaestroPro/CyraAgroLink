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
        Schema::create('messaging_sms_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 16)->default('outbound'); // outbound|inbound
            $table->string('to_phone', 32);
            $table->string('from_phone', 32)->nullable();
            $table->string('body', 480);
            $table->string('status', 24)->default('sent'); // draft|queued|sent|failed|delivered
            $table->string('provider', 40)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('messaging_email_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 16)->default('outbound'); // outbound|inbound
            $table->string('to_email', 160);
            $table->string('from_email', 160)->nullable();
            $table->string('subject', 200);
            $table->string('body', 5000);
            $table->string('status', 24)->default('sent'); // draft|queued|sent|failed|opened
            $table->string('provider', 40)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('messaging_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('body', 1000)->nullable();
            $table->string('priority', 16)->default('medium'); // low|medium|high
            $table->string('status', 24)->default('open'); // open|in_progress|done|cancelled
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('source', 40)->default('manual'); // manual|notification|system
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_at']);
        });

        Schema::create('user_announcement_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_announcement_id')->constrained()->cascadeOnDelete();
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'platform_announcement_id'], 'user_announcement_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_announcement_reads');
        Schema::dropIfExists('messaging_tasks');
        Schema::dropIfExists('messaging_email_messages');
        Schema::dropIfExists('messaging_sms_messages');
    }
};
