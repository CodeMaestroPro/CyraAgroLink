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
        Schema::create('investment_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('location', 150);
            $table->decimal('roi_percent', 8, 2);
            $table->unsignedSmallInteger('duration_months');
            $table->unsignedBigInteger('amount');
            $table->unsignedTinyInteger('funded_percent')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status', 32)->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_opportunities');
    }
};
