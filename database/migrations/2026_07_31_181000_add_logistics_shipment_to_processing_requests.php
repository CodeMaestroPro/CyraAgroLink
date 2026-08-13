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
        Schema::table('processing_requests', function (Blueprint $table): void {
            $table->foreignId('logistics_shipment_id')
                ->nullable()
                ->after('factory_id')
                ->constrained('logistics_shipments')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('processing_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('logistics_shipment_id');
        });
    }
};
