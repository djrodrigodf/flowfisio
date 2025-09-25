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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();

            // Convênio e local podem ser nulos (definem preço/sala)
            $table->foreignId('insurance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('start_at');
            $table->timestamp('end_at');

            $table->enum('status', ['scheduled', 'attended', 'no_show', 'rescheduled', 'canceled'])
                ->default('scheduled');

            // Snapshot financeiro
            $table->decimal('price_base', 10, 2)->default(0);          // preço antes do desconto
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();       // valor do desconto
            $table->decimal('price_final', 10, 2)->default(0);          // após desconto
            $table->decimal('payout_value_snapshot', 10, 2)->default(0); // repasse calculado no ato

            $table->json('pricing_meta')->nullable(); // ids de price/payout usados, observações etc.
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices para buscas e checagens
            $table->index(['professional_id', 'start_at']);
            $table->index(['room_id', 'start_at']);
            $table->index(['patient_id', 'start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
