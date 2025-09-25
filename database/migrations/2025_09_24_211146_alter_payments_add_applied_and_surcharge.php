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
        Schema::table('payments', function (Blueprint $table) {
            // parte do pagamento que efetivamente ABATE o débito do appointment
            $table->decimal('applied_to_due', 10, 2)->nullable()->after('amount_paid');

            // excedente acima do devido (juros, multa, taxa, doação etc.)
            $table->decimal('surcharge_amount', 10, 2)->default(0)->after('applied_to_due');
            $table->string('surcharge_reason')->nullable()->after('surcharge_amount');
        });

        // Backfill simples: onde já está "paid" e não tem novo campo, considera tudo aplicado ao débito.
        DB::table('payments')
            ->where('status', 'paid')
            ->whereNull('applied_to_due')
            ->update(['applied_to_due' => DB::raw('amount_paid')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
