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
        Schema::create('restrictions', function (Blueprint $table) {
            $table->id();

            // Escopo da restrição
            // global | location | room | professional
            $table->enum('scope', ['global', 'location', 'room', 'professional'])->nullable();
            $table->unsignedBigInteger('scope_id')->nullable(); // referência dinâmica (sem FK)

            // Período da restrição
            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->string('reason', 255)->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Índices p/ performance
            $table->index(['scope', 'scope_id']);
            $table->index('start_at');
            $table->index('end_at');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restrictions');
    }
};
