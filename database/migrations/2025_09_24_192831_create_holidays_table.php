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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // se recorrente, só o mês/dia importam; ainda assim gravamos a data com ano (para históricos/estatísticas)
            $table->date('date');                 // ex.: 2025-12-25
            $table->boolean('is_recurring')->default(true); // repete a cada ano (compara por mês/dia)

            // mesmo padrão de escopo das restrições
            $table->enum('scope', ['location', 'room', 'professional'])->nullable(); // Global = null
            $table->unsignedBigInteger('scope_id')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['date', 'is_recurring']);
            $table->index(['scope', 'scope_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
