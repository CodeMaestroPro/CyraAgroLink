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
        Schema::create('weather_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location_key', 80);
            $table->string('location_label', 160);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedTinyInteger('temperature_c');
            $table->string('condition', 80);
            $table->string('icon', 40);
            $table->unsignedTinyInteger('humidity_pct');
            $table->decimal('rainfall_mm', 6, 1);
            $table->unsignedTinyInteger('wind_kmh');
            $table->json('forecast');
            $table->json('rainfall_zones');
            $table->string('recommendation', 400);
            $table->string('source', 40)->default('model'); // model|open_meteo
            $table->dateTime('observed_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'observed_at']);
            $table->index(['user_id', 'location_key']);
        });

        Schema::create('weather_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weather_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('alert_key', 80);
            $table->string('title', 160);
            $table->string('detail', 500);
            $table->string('icon', 40)->default('storm'); // storm|heat
            $table->string('severity', 16)->default('medium'); // low|medium|high
            $table->string('status', 24)->default('open'); // open|acknowledged|dismissed
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('dismissed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'alert_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_alerts');
        Schema::dropIfExists('weather_snapshots');
    }
};
