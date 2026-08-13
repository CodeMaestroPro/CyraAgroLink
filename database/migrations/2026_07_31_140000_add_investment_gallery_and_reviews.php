<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_opportunities', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image_path');
            $table->text('summary')->nullable()->after('location');
        });

        Schema::create('investment_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investment_opportunity_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->timestamps();

            $table->unique(['user_id', 'investment_opportunity_id']);
            $table->index(['investment_opportunity_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_reviews');

        Schema::table('investment_opportunities', function (Blueprint $table) {
            $table->dropColumn(['images', 'summary']);
        });
    }
};
