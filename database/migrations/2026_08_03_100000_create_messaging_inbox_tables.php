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
        Schema::create('user_inbox_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('body', 500);
            $table->string('tone', 40)->default('system'); // payment|order|investment|weather|message|system
            $table->string('category', 40)->default('alert');
            $table->string('notification_key', 80)->nullable();
            $table->dateTime('read_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'notification_key']);
        });

        Schema::create('messaging_contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_online')->default(false);
            $table->boolean('is_system')->default(true);
            $table->foreignId('linked_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('message_threads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('messaging_contact_id')->constrained()->cascadeOnDelete();
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('last_read_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'messaging_contact_id']);
            $table->index(['user_id', 'last_message_at']);
        });

        Schema::create('thread_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 16); // incoming|outgoing
            $table->string('body', 2000);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['message_thread_id', 'created_at']);
        });

        Schema::create('platform_announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->string('body', 500);
            $table->string('audience', 40)->default('all');
            $table->boolean('is_active')->default(true);
            $table->dateTime('published_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('thread_messages');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('messaging_contacts');
        Schema::dropIfExists('user_inbox_notifications');
    }
};
