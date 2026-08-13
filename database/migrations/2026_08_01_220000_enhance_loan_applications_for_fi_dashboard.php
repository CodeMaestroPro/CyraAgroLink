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
        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->string('purpose', 200)->nullable()->after('sector');
            $table->unsignedBigInteger('amount_repaid')->default(0)->after('amount');
            $table->dateTime('disbursed_at')->nullable()->after('reviewed_at');
            $table->dateTime('closed_at')->nullable()->after('disbursed_at');
        });

        Schema::create('loan_repayments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('note', 255)->nullable();
            $table->dateTime('paid_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['loan_application_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');

        Schema::table('loan_applications', function (Blueprint $table): void {
            $table->dropColumn(['purpose', 'amount_repaid', 'disbursed_at', 'closed_at']);
        });
    }
};
