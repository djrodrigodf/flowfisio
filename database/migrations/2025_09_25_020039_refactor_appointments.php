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
            // 1) professional_id -> partner_id (troca FK)
            if (Schema::hasColumn('appointments', 'professional_id')) {
                $table->dropForeign(['professional_id']);
                $table->renameColumn('professional_id', 'partner_id');
            }

            // 2) cria FK para partners
            $table->foreign('partner_id')
                ->references('id')->on('partners')
                ->cascadeOnDelete();

            // 3) novos campos usados pelo form/service atuais (se não existirem)
            if (! Schema::hasColumn('appointments', 'duration_min')) {
                $table->unsignedSmallInteger('duration_min')->nullable()->after('end_at');
            }
            if (! Schema::hasColumn('appointments', 'price')) {
                $table->decimal('price', 12, 2)->nullable()->after('status');
            }
            if (! Schema::hasColumn('appointments', 'repasse_type')) {
                $table->enum('repasse_type', ['percent', 'fixed'])->nullable()->after('price');
            }
            if (! Schema::hasColumn('appointments', 'repasse_value')) {
                $table->decimal('repasse_value', 12, 2)->nullable()->after('repasse_type');
            }
            if (! Schema::hasColumn('appointments', 'treatment_table_id')) {
                $table->foreignId('treatment_table_id')->nullable()
                    ->after('repasse_value')
                    ->constrained('treatment_tables')->nullOnDelete();
            }
        });

        // (Opcional) Remover os campos antigos de snapshot financeiro se **não** forem mais usados:
        Schema::table('appointments', function (Blueprint $table) {
            foreach (['price_base', 'discount_type', 'discount_value', 'price_final', 'payout_value_snapshot', 'pricing_meta'] as $col) {
                if (Schema::hasColumn('appointments', $col)) {
                    $table->dropColumn($col);
                }
            }
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
