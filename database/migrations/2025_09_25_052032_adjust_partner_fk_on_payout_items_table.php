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
        // 1) adiciona partner_id se ainda não existir
        Schema::table('payout_items', function (Blueprint $table) {
            if (! Schema::hasColumn('payout_items', 'partner_id')) {
                // Ajuste a posição conforme preferir (ex.: after('appointment_id'))
                $table->foreignId('partner_id')
                    ->nullable()
                    ->after('appointment_id')
                    ->constrained('partners')
                    ->nullOnDelete();
            }
        });

        // 2) copia dados da coluna antiga (se existir)
        if (Schema::hasColumn('payout_items', 'professional_id')) {
            DB::statement('UPDATE payout_items SET partner_id = professional_id WHERE partner_id IS NULL');
        }

        // 3) remove FK + coluna antiga
        if (Schema::hasColumn('payout_items', 'professional_id')) {
            Schema::table('payout_items', function (Blueprint $table) {
                try {
                    $table->dropConstrainedForeignId('professional_id');
                } catch (\Throwable $e) {
                    try {
                        $table->dropForeign(['professional_id']);
                    } catch (\Throwable $e) {
                    }
                }

                $table->dropColumn('professional_id');
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
