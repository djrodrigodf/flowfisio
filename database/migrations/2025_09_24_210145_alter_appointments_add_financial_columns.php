<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('financial_status', ['pending', 'partial', 'paid', 'exempt'])
                ->default('pending')
                ->after('status');
            $table->decimal('paid_total', 10, 2)->default(0)->after('price_final');
            $table->timestamp('paid_at')->nullable()->after('paid_total');
            $table->string('financial_notes')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
