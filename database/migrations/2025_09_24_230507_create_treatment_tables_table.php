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
        Schema::create('treatment_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            // Escopo (opcionais)
            $table->foreignId('insurance_id')->nullable()->constrained('insurances')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            // No projeto usamos Doctors
            $table->foreignId('partner_id')
                ->nullable()
                ->constrained('partners')
                ->nullOnDelete();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->unsignedTinyInteger('priority')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Índices úteis para busca por escopo e vigência
            $table->index(['status', 'effective_from', 'effective_to']);
            $table->index(['insurance_id', 'location_id', 'partner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_tables');
    }
};
