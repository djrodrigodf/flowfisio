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
        Schema::create('treatment_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_id')->nullable()->constrained()->nullOnDelete(); // null = particular
            $table->decimal('price', 10, 2);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->timestamps();

            // Índices úteis para busca por vigência
            $table->index(['treatment_id', 'insurance_id', 'starts_at']);
            $table->index(['treatment_id', 'insurance_id', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_prices');
    }
};
