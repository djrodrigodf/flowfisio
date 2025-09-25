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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->enum('status', ['open', 'approved', 'paid', 'canceled'])->default('open')->index();

            $table->decimal('gross_total', 12, 2)->default(0);       // soma dos itens (repasse de cada atendimento)
            $table->decimal('adjustments_total', 12, 2)->default(0); // soma dos ajustes (pode ser negativo)
            $table->decimal('net_total', 12, 2)->default(0);         // gross + adjustments

            $table->timestamp('paid_at')->nullable();
            $table->string('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['professional_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
