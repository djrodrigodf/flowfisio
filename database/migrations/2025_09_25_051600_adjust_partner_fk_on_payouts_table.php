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
        Schema::table('payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('payouts', 'partner_id')) {
                $table->foreignId('partner_id')->nullable()->after('id')
                    ->constrained('partners')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('payouts', 'professional_id')) {
            DB::statement('UPDATE payouts SET partner_id = professional_id WHERE partner_id IS NULL');
            Schema::table('payouts', function (Blueprint $table) {
                // Remova FK antiga se existir e a coluna
                try {
                    $table->dropConstrainedForeignId('professional_id');
                } catch (\Throwable $e) {
                }
                if (Schema::hasColumn('payouts', 'professional_id')) {
                    $table->dropColumn('professional_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
