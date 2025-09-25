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
        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();

            // denormalizações úteis
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->date('service_date'); // data do atendimento (start_at->date)

            // valor de repasse (snapshot do appointment no momento do fechamento)
            $table->decimal('payout_value', 12, 2)->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('appointment_id'); // garante que 1 atendimento entre apenas uma vez
            $table->index(['payout_id', 'service_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_items');
    }
};
