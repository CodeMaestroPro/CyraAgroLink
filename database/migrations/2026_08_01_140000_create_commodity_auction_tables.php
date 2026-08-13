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
        Schema::create('commodity_auctions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('commodity', 80);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('quantity_tons')->default(1);
            $table->unsignedBigInteger('starting_bid_ngn');
            $table->unsignedBigInteger('current_bid_ngn');
            $table->unsignedBigInteger('min_increment_ngn')->default(5000);
            $table->foreignId('highest_bidder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('highest_bidder_name', 120)->nullable();
            $table->string('status', 24)->default('live'); // live|ended|cancelled
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('winning_bid_ngn')->nullable();
            $table->dateTime('settled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
            $table->index(['commodity', 'status']);
        });

        Schema::create('auction_bids', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained('commodity_auctions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 32);
            $table->unsignedBigInteger('amount_ngn');
            $table->string('bidder_label', 120);
            $table->string('status', 24)->default('leading'); // leading|outbid|won|refunded
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reference']);
            $table->index(['auction_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_bids');
        Schema::dropIfExists('commodity_auctions');
    }
};
