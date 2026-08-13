<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->boolean('is_seeded')->default(false)->after('status');
        });

        // Starter portfolio rows should not consume marketplace raise capacity.
        DB::table('user_investments')
            ->whereIn('amount', [800000, 700000, 850000, 600000, 550000, 204000, 404000])
            ->where('accrued_earnings', '>', 0)
            ->update(['is_seeded' => true]);
    }

    public function down(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropColumn('is_seeded');
        });
    }
};
